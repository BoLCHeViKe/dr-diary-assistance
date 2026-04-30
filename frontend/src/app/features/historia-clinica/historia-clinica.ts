import { Component, EventEmitter, Input, OnInit, Output, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { HcService, DetalleHc, DetalleHcForm } from '../../core/services/hc.service';
import { AuthService } from '../../core/services/auth.service';
import { Cita } from '../../core/services/agenda.service';

type HcMode = 'ver' | 'añadir' | 'editar';

@Component({
  selector: 'app-historia-clinica',
  imports: [ReactiveFormsModule],
  templateUrl: './historia-clinica.html',
  styleUrl: './historia-clinica.scss',
})
export class HistoriaClinicaComponent implements OnInit {
  private readonly svc  = inject(HcService);
  private readonly auth = inject(AuthService);
  private readonly fb   = inject(FormBuilder);

  @Input() nhc!: number;
  @Input() idPaciente!: number;
  @Input() modoInicial: 'ver' | 'añadir' = 'ver';
  @Input() citaParaAnadir?: Cita;

  @Output() cerrar           = new EventEmitter<void>();
  @Output() consultaGuardada = new EventEmitter<void>();

  // ── State ─────────────────────────────────────────────────────────────────
  modo            = signal<HcMode>('ver');
  detalles        = signal<DetalleHc[]>([]);
  detalleEditando = signal<DetalleHc | null>(null);
  loading         = signal(false);
  saving          = signal(false);
  error           = signal('');

  // ── Form ──────────────────────────────────────────────────────────────────
  form = this.fb.nonNullable.group({
    mov_consulta: ['', [Validators.required, Validators.maxLength(32)]],
    tto:          ['', Validators.maxLength(60)],
    sinto:        [''],
    diag:         ['', Validators.maxLength(80)],
  });

  // ── Helpers ───────────────────────────────────────────────────────────────
  get currentUserId(): number { return this.auth.getUser()?.id ?? 0; }

  canEdit(d: DetalleHc): boolean {
    return d.cita?.agenda?.id_med === this.currentUserId;
  }

  // ── Lifecycle ─────────────────────────────────────────────────────────────
  ngOnInit() {
    this.modo.set(this.modoInicial);
    this.loadDetalles();
  }

  loadDetalles() {
    this.loading.set(true);
    this.error.set('');
    const obs = this.nhc
      ? this.svc.getDetalles(this.nhc)
      : this.svc.getHcPorPaciente(this.idPaciente);

    obs.subscribe({
      next: (res) => { this.detalles.set(res.detalles); this.loading.set(false); },
      error: ()   => { this.error.set('Error al cargar la Historia Clínica.'); this.loading.set(false); },
    });
  }

  // ── Añadir episodio ───────────────────────────────────────────────────────
  abrirAnadir() {
    this.form.reset();
    this.detalleEditando.set(null);
    this.error.set('');
    this.modo.set('añadir');
  }

  guardarDetalle() {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    this.error.set('');
    const v = this.form.getRawValue();
    const data: DetalleHcForm = {
      mov_consulta: v.mov_consulta || undefined,
      tto:          v.tto,
      sinto:        v.sinto || undefined,
      diag:         v.diag  || undefined,
      id_cita:      this.citaParaAnadir?.id_cita,
    };

    const nhcEfectivo = this.nhc;
    this.svc.addDetalle(nhcEfectivo, data).subscribe({
      next: () => {
        this.saving.set(false);
        this.consultaGuardada.emit();
        this.loadDetalles();
        this.modo.set('ver');
      },
      error: (e) => {
        this.error.set(e.error?.error ?? e.error?.message ?? 'Error al guardar.');
        this.saving.set(false);
      },
    });
  }

  // ── Editar episodio ───────────────────────────────────────────────────────
  abrirEditar(d: DetalleHc) {
    this.detalleEditando.set(d);
    this.error.set('');
    this.form.patchValue({
      mov_consulta: d.mov_consulta ?? '',
      tto:          d.tto,
      sinto:        d.sinto ?? '',
      diag:         d.diag  ?? '',
    });
    this.modo.set('editar');
  }

  actualizarDetalle() {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    const d = this.detalleEditando();
    if (!d) return;
    this.saving.set(true);
    this.error.set('');
    const v = this.form.getRawValue();
    this.svc.updateDetalle(this.nhc, d.num_orden, {
      mov_consulta: v.mov_consulta || undefined,
      tto:          v.tto,
      sinto:        v.sinto || undefined,
      diag:         v.diag  || undefined,
    }).subscribe({
      next: () => {
        this.saving.set(false);
        this.loadDetalles();
        this.modo.set('ver');
      },
      error: (e) => {
        this.error.set(e.error?.error ?? e.error?.message ?? 'Error al actualizar.');
        this.saving.set(false);
      },
    });
  }

  cancelar() {
    this.modo.set('ver');
    this.detalleEditando.set(null);
    this.error.set('');
    this.form.reset();
  }

  formatFecha(f: string): string {
    if (!f || f.startsWith('1900')) return '—';
    return new Date(f).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }
}
