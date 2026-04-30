import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { GestionService, GestionEspecialidad } from '../../../core/services/gestion.service';

type PanelMode = null | 'create' | 'edit';

@Component({
  selector: 'app-gestion-especialidades',
  imports: [ReactiveFormsModule],
  templateUrl: './especialidades.html',
  styleUrl: './especialidades.scss',
})
export class GestionEspecialidadesComponent implements OnInit {
  private readonly svc = inject(GestionService);
  private readonly fb  = inject(FormBuilder);

  especialidades = signal<GestionEspecialidad[]>([]);
  panelMode      = signal<PanelMode>(null);
  editingEsp     = signal<GestionEspecialidad | null>(null);
  saving         = signal(false);
  error          = signal('');
  formError      = signal('');

  form = this.fb.nonNullable.group({
    codigo_esp: ['', [Validators.required, Validators.maxLength(4), Validators.pattern(/^[A-Za-z]{1,4}$/)]],
    nombre:     ['', [Validators.required, Validators.maxLength(30)]],
  });

  ngOnInit() { this.load(); }

  load() {
    this.svc.getEspecialidades().subscribe({
      next: (list) => this.especialidades.set(list),
      error: () => this.error.set('Error al cargar las especialidades.'),
    });
  }

  openCreate() {
    this.form.reset();
    this.form.get('codigo_esp')!.enable();
    this.editingEsp.set(null);
    this.formError.set('');
    this.panelMode.set('create');
  }

  openEdit(esp: GestionEspecialidad) {
    this.editingEsp.set(esp);
    this.form.patchValue({ codigo_esp: esp.codigo_esp, nombre: esp.nombre });
    this.form.get('codigo_esp')!.disable(); // PK no editable
    this.formError.set('');
    this.panelMode.set('edit');
  }

  closePanel() {
    this.panelMode.set(null);
    this.editingEsp.set(null);
    this.form.reset();
    this.form.get('codigo_esp')!.enable();
  }

  submit() {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.getRawValue();

    const obs = this.panelMode() === 'create'
      ? this.svc.createEspecialidad({ codigo_esp: raw.codigo_esp.toUpperCase(), nombre: raw.nombre })
      : this.svc.updateEspecialidad(this.editingEsp()!.codigo_esp, { nombre: raw.nombre });

    obs.subscribe({
      next: () => { this.saving.set(false); this.closePanel(); this.load(); },
      error: (e) => { this.formError.set(e.error?.error ?? e.error?.message ?? 'Error.'); this.saving.set(false); },
    });
  }

  toggle(esp: GestionEspecialidad) {
    const accion = esp.activo ? 'deshabilitar' : 'habilitar';
    if (!confirm(`¿${accion} la especialidad "${esp.nombre}"?`)) return;
    this.svc.toggleEspecialidad(esp.codigo_esp).subscribe({
      next: () => this.load(),
      error: (e) => this.error.set(e.error?.message ?? 'Error.'),
    });
  }
}
