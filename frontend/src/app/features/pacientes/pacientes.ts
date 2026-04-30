import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { PacienteService, Paciente } from '../../core/services/paciente.service';
import { PacienteSelectorComponent } from '../../shared/components/paciente-selector/paciente-selector';
import { HistoriaClinicaComponent } from '../historia-clinica/historia-clinica';

type PanelMode = null | 'create' | 'edit' | 'view-hc';

@Component({
  selector: 'app-pacientes',
  imports: [ReactiveFormsModule, PacienteSelectorComponent, HistoriaClinicaComponent],
  templateUrl: './pacientes.html',
  styleUrl: './pacientes.scss',
})
export class PacientesComponent {
  private readonly svc = inject(PacienteService);
  private readonly fb  = inject(FormBuilder);

  // ── State ─────────────────────────────────────────────────────────────────
  pacienteActivo = signal<Paciente | null>(null);
  panelMode      = signal<PanelMode>(null);
  saving         = signal(false);
  error          = signal('');
  formError      = signal('');
  refreshTrigger = signal(0);

  // ── Form ──────────────────────────────────────────────────────────────────
  form = this.fb.nonNullable.group({
    nombre:    ['', [Validators.required, Validators.maxLength(30)]],
    apellido1: ['', [Validators.required, Validators.maxLength(30)]],
    apellido2: ['', Validators.maxLength(30)],
    fecha_nac: [''],
    dni:       ['', [Validators.required, Validators.pattern(/^\d{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i)]],
    telf:      ['', Validators.maxLength(13)],
    email:     ['', [Validators.email, Validators.maxLength(64)]],
    direccion: ['', Validators.maxLength(100)],
  });

  // ── Selector events ───────────────────────────────────────────────────────
  onSeleccionado(p: Paciente) {
    this.pacienteActivo.set(p);
    this.error.set('');
  }

  onAbierto(p: Paciente) {
    this.pacienteActivo.set(p);
    this.openEdit(p);
  }

  // ── Panel ─────────────────────────────────────────────────────────────────
  openCreate() {
    this.form.reset();
    this.formError.set('');
    this.pacienteActivo.set(null);
    this.panelMode.set('create');
  }

  openEdit(p?: Paciente) {
    const pat = p ?? this.pacienteActivo();
    if (!pat) return;
    this.form.patchValue({
      nombre:    pat.nombre,
      apellido1: pat.apellido1,
      apellido2: pat.apellido2 ?? '',
      fecha_nac: pat.fecha_nac ?? '',
      dni:       pat.dni,
      telf:      pat.telf ?? '',
      email:     pat.email ?? '',
      direccion: pat.direccion ?? '',
    });
    this.formError.set('');
    this.panelMode.set('edit');
  }

  closePanel() {
    this.panelMode.set(null);
    this.form.reset();
    this.formError.set('');
  }

  verHC() {
    const p = this.pacienteActivo();
    if (!p || !p.nhc) return;
    this.panelMode.set('view-hc');
  }

  // ── CRUD ──────────────────────────────────────────────────────────────────
  submitForm() {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.getRawValue();

    if (this.panelMode() === 'create') {
      this.svc.create(raw).subscribe({
        next: () => {
          this.saving.set(false);
          this.closePanel();
          this.refreshTrigger.update(n => n + 1);
        },
        error: (e) => { this.formError.set(this.parseErr(e)); this.saving.set(false); },
      });
    } else {
      const id = this.pacienteActivo()!.id_paciente;
      this.svc.update(id, raw).subscribe({
        next: (updated) => {
          this.pacienteActivo.set(updated);
          this.saving.set(false);
          this.closePanel();
          this.refreshTrigger.update(n => n + 1);
        },
        error: (e) => { this.formError.set(this.parseErr(e)); this.saving.set(false); },
      });
    }
  }

  deletePaciente() {
    const p = this.pacienteActivo();
    if (!p) return;
    if (!confirm(`¿Eliminar a ${p.nombre} ${p.apellido1}?\nSe eliminará también su Historia Clínica. Esta acción es irreversible.`)) return;
    this.svc.delete(p.id_paciente).subscribe({
      next: () => {
        this.pacienteActivo.set(null);
        this.refreshTrigger.update(n => n + 1);
      },
      error: (e) => { this.error.set(this.parseErr(e)); },
    });
  }

  // ── Helpers ───────────────────────────────────────────────────────────────
  field(name: string) { return this.form.get(name)!; }
  invalid(name: string) { return this.field(name).invalid && this.field(name).touched; }

  private parseErr(e: any): string {
    return e.error?.error ?? e.error?.message ?? 'Error en la operación.';
  }
}
