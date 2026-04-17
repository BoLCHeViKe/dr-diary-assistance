<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // --- 1. SECUENCIALIDAD ---
        // DetalleHC: num_orden automático
        DB::unprepared("
            CREATE TRIGGER trg_detallehc_num_orden_auto
            BEFORE INSERT ON detallehc FOR EACH ROW
            BEGIN
                DECLARE next_num INT;
                SELECT IFNULL(MAX(num_orden), 0) + 1 INTO next_num FROM detallehc WHERE nhc = NEW.nhc;
                SET NEW.num_orden = next_num;
            END
        ");

        // Lineafactura: num_linea automático
        DB::unprepared("
            CREATE TRIGGER trg_lineafactura_num_linea_auto
            BEFORE INSERT ON lineafactura FOR EACH ROW
            BEGIN
                DECLARE next_num INT;
                SELECT IFNULL(MAX(num_linea), 0) + 1 INTO next_num FROM lineafactura WHERE num_fact = NEW.num_fact;
                SET NEW.num_linea = next_num;
            END
        ");

        // --- 2. FECHAS AUTOMÁTICAS (1900-01-01 -> NOW) ---
        // Historia Clínica
        DB::unprepared("
            CREATE TRIGGER trg_hc_fecha_apert_now
            BEFORE INSERT ON hc FOR EACH ROW
            BEGIN
                IF NEW.fecha_apert = '1900-01-01' THEN SET NEW.fecha_apert = DATE(NOW()); END IF;
            END
        ");

        // Factura
        DB::unprepared("
            CREATE TRIGGER trg_factura_fecha_now
            BEFORE INSERT ON factura FOR EACH ROW
            BEGIN
                IF NEW.fecha = '1900-01-01' THEN SET NEW.fecha = DATE(NOW()); END IF;
            END
        ");

        // --- 3. VALIDACIÓN DE FECHAS (Integridad HC) ---
        // DetalleHC: No antes de la apertura (INSERT)
        DB::unprepared("
            CREATE TRIGGER trg_detallehc_comprobar_fecha_ins
            BEFORE INSERT ON detallehc FOR EACH ROW
            BEGIN
                DECLARE apertura_hc DATE;
                SELECT fecha_apert INTO apertura_hc FROM hc WHERE nhc = NEW.nhc;
                IF NEW.f_consulta = '1900-01-01' THEN SET NEW.f_consulta = DATE(NOW()); END IF;
                IF NEW.f_consulta < apertura_hc THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de la consulta no puede ser anterior a la apertura de la HC.';
                END IF;
            END
        ");

        // DetalleHC: No antes de la apertura (UPDATE)
        DB::unprepared("
            CREATE TRIGGER trg_detallehc_comprobar_fecha_upd
            BEFORE UPDATE ON detallehc FOR EACH ROW
            BEGIN
                DECLARE apertura_hc DATE;
                SELECT fecha_apert INTO apertura_hc FROM hc WHERE nhc = NEW.nhc;
                IF NEW.f_consulta < apertura_hc THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de la consulta no puede ser anterior a la apertura de la HC.';
                END IF;
            END
        ");

        // --- 4. PRECIOS Y TOTALES AUTOMÁTICOS ---
        DB::unprepared("
            CREATE TRIGGER trg_lineafactura_precio_total_auto
            BEFORE INSERT ON lineafactura FOR EACH ROW
            BEGIN
                DECLARE precio_calc DECIMAL(10,2);
                SELECT p.precio INTO precio_calc FROM prestacion AS p 
                WHERE p.codigo_esp = NEW.codigo_esp AND p.id_prest = NEW.id_prest;
                
                SET NEW.precio = precio_calc;
                SET NEW.total = NEW.cantidad * NEW.precio;
            END
        ");

        // --- 5. BLOQUEOS DE EDICIÓN (FACTURACIÓN CERRADA) ---
        // Bloqueo de Factura (Update)
        DB::unprepared("
            CREATE TRIGGER trg_factura_bloquear_edicion
            BEFORE UPDATE ON factura FOR EACH ROW
            BEGIN
                IF OLD.estado = 'emitida' AND NEW.estado != 'anulada' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede modificar una factura emitida (solo anular).';
                END IF;
                IF OLD.estado = 'anulada' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede modificar una factura anulada.';
                END IF;
            END
        ");

        // Bloqueo de Líneas (Update)
        DB::unprepared("
            CREATE TRIGGER trg_lineafactura_bloquear_cambios
            BEFORE UPDATE ON lineafactura FOR EACH ROW
            BEGIN
                DECLARE v_estado VARCHAR(20);
                SELECT estado INTO v_estado FROM factura WHERE num_fact = NEW.num_fact;
                IF v_estado != 'borrador' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo se pueden alterar líneas en facturas BORRADOR.';
                END IF;
            END
        ");

        // Bloqueo de Líneas (Delete)
        DB::unprepared("
            CREATE TRIGGER trg_lineafactura_bloquear_borrado
            BEFORE DELETE ON lineafactura FOR EACH ROW
            BEGIN
                DECLARE v_estado VARCHAR(20);
                SELECT estado INTO v_estado FROM factura WHERE num_fact = OLD.num_fact;
                IF v_estado != 'borrador' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden eliminar líneas de facturas cerradas.';
                END IF;
            END
        ");

        // --- 6. SEGURIDAD DE ABONOS (SIGNOS Y LÍMITES) ---
        DB::unprepared("
            CREATE TRIGGER trg_lineafactura_seguridad_total
            BEFORE INSERT ON lineafactura FOR EACH ROW
            BEGIN
                DECLARE v_estado VARCHAR(20);
                DECLARE v_fact_ref INT;
                DECLARE v_max_abonable DECIMAL(10,2);
                DECLARE v_ya_abonado DECIMAL(10,2);

                SELECT estado, fact_ref INTO v_estado, v_fact_ref FROM factura WHERE num_fact = NEW.num_fact;

                IF v_estado != 'borrador' AND v_estado != 'abono' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Factura cerrada: No admite nuevas líneas.';
                END IF;

                IF v_estado = 'abono' THEN
                    IF NEW.precio > 0 THEN SET NEW.precio = NEW.precio * -1; END IF;
                    IF NEW.total > 0 THEN SET NEW.total = NEW.total * -1; END IF;
                    
                    IF v_fact_ref IS NOT NULL THEN
                        SELECT IFNULL(SUM(total), 0) INTO v_max_abonable FROM lineafactura WHERE num_fact = v_fact_ref;
                        SELECT ABS(IFNULL(SUM(total), 0)) INTO v_ya_abonado FROM lineafactura
                        WHERE num_fact IN (SELECT num_fact FROM factura WHERE fact_ref = v_fact_ref);

                        IF (v_ya_abonado + ABS(NEW.total)) > v_max_abonable THEN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Abono supera el total de la factura original.';
                        END IF;
                    END IF;
                ELSE
                    IF NEW.total < 0 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Importes negativos solo en ABONOS.';
                    END IF;
                END IF;
            END
        ");
    }

    public function down(): void
    {
        // Limpieza de todos los triggers
        $triggers = [
            'trg_detallehc_num_orden_auto', 'trg_lineafactura_num_linea_auto',
            'trg_hc_fecha_apert_now', 'trg_factura_fecha_now',
            'trg_detallehc_comprobar_fecha_ins', 'trg_detallehc_comprobar_fecha_upd',
            'trg_lineafactura_precio_total_auto', 'trg_factura_bloquear_edicion',
            'trg_lineafactura_bloquear_cambios', 'trg_lineafactura_bloquear_borrado',
            'trg_lineafactura_seguridad_total'
        ];
        foreach ($triggers as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS $trigger");
        }
    }
};
