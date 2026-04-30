import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Paciente } from './paciente.service';

export interface LineaFactura {
  num_linea: number;
  num_fact:  number;
  cantidad:  number;
  codigo_esp: string;
  id_prest:  number;
  precio:    number;
  total:     number;
  prestacion?: { nombre: string; descripcion?: string; precio: number };
}

export interface Factura {
  num_fact:     number;
  fecha:        string;
  estado:       'borrador' | 'emitida' | 'anulada' | 'abono';
  id_paciente:  number;
  fact_ref?:    number;
  paciente?:    Paciente;
  lineas:       LineaFactura[];
  abonos?:      Factura[];
  importe_calc?: number;
  abonado_calc?: number;
}

export interface FacturasResponse {
  facturas:    Factura[];
  total_items: number;
  totales: {
    emitidas:        number;
    anuladas_abonos: number;
    neto:            number;
  };
}

export interface FacturaFilters {
  desde_fecha?:  string;
  hasta_fecha?:  string;
  id_paciente?:  number;
  estados?:      string;
  page?:         number;
}

@Injectable({ providedIn: 'root' })
export class FacturaService {
  private readonly http = inject(HttpClient);

  getFacturas(filters: FacturaFilters) {
    let params = new HttpParams();
    if (filters.desde_fecha) params = params.set('desde_fecha', filters.desde_fecha);
    if (filters.hasta_fecha) params = params.set('hasta_fecha',  filters.hasta_fecha);
    if (filters.id_paciente) params = params.set('id_paciente',  filters.id_paciente);
    if (filters.estados)     params = params.set('estados',       filters.estados);
    if (filters.page)        params = params.set('page',          filters.page);
    return this.http.get<FacturasResponse>('/api/facturas', { params });
  }

  getFactura(numFact: number) {
    return this.http.get<Factura>(`/api/facturas/${numFact}`);
  }
}
