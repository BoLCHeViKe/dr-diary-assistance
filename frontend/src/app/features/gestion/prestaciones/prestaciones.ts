import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { GestionService, GestionPrestacion, GestionEspecialidad } from '../../../core/services/gestion.service';
import { CurrencyPipe } from '@angular/common';

type PanelMode = null | 'create' | 'edit';

@Component({
  selector: 'app-gestion-prestaciones',
  imports: [ReactiveFormsModule, CurrencyPipe],
  templateUrl: './prestaciones.html',
  styleUrl: './prestaciones.scss',
})
export class GestionPrestacionesComponent implements OnInit {
  private readonly svc = inject(GestionService);
  private readonly fb  = inject(FormBuilder);

  prestaciones   = signal<GestionPrestacion[]>([]);
  especialidades = signal<GestionEspecialidad[]>([]);
  filtroEsp      = signal<string>('');
  panelMode      = signal<PanelMode>(null);
  editingPrest   = signal<GestionPrestacion | null>(null);
  saving         = signal(false);
  error          = signal('');
  formError      = signal('');

  prestacionesFiltradas = computed(() => {
    const f = this.filtroEsp();
    return f ? this.prestaciones().filter(p => p.codigo_esp === f) : this.prestaciones();
  });

  form = this.fb.nonNullable.group({
    codigo_esp:  ['', Validators.required],
    nombre:      ['', [Validators.required, Validators.maxLength(30)]],
    descripcion: ['', Validators.maxLength(80)],
    precio:      [0, [Validators.required, Validators.min(0)]],
  });

  ngOnInit() {
    this.load();
    this.svc.getEspecialidades().subscribe(list => this.especialidades.set(list));
  }

  load() {
    this.svc.getPrestaciones().subscribe({
      next: (list) => this.prestaciones.set(list),
      error: () => this.error.set('Error al cargar las prestaciones.'),
    });
  }

  openCreate() {
    this.form.reset({ precio: 0 });
    this.form.get('codigo_esp')!.enable();
    this.editingPrest.set(null);
    this.formError.set('');
    this.panelMode.set('create');
  }

  openEdit(p: GestionPrestacion) {
    this.editingPrest.set(p);
    this.form.patchValue({ codigo_esp: p.codigo_esp, nombre: p.nombre, descripcion: p.descripcion ?? '', precio: p.precio });
    this.form.get('codigo_esp')!.disable();
    this.formError.set('');
    this.panelMode.set('edit');
  }

  closePanel() {
    this.panelMode.set(null);
    this.editingPrest.set(null);
    this.form.reset({ precio: 0 });
    this.form.get('codigo_esp')!.enable();
  }

  submit() {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.getRawValue();

    const obs = this.panelMode() === 'create'
      ? this.svc.createPrestacion({ codigo_esp: raw.codigo_esp, nombre: raw.nombre, descripcion: raw.descripcion || undefined, precio: raw.precio })
      : this.svc.updatePrestacion(this.editingPrest()!.codigo_esp, this.editingPrest()!.id_prest, { nombre: raw.nombre, descripcion: raw.descripcion || undefined, precio: raw.precio });

    obs.subscribe({
      next: () => { this.saving.set(false); this.closePanel(); this.load(); },
      error: (e) => { this.formError.set(e.error?.error ?? e.error?.message ?? 'Error.'); this.saving.set(false); },
    });
  }

  toggle(p: GestionPrestacion) {
    const accion = p.activo ? 'deshabilitar' : 'habilitar';
    if (!confirm(`¿${accion} la prestación "${p.nombre}"?`)) return;
    this.svc.togglePrestacion(p.codigo_esp, p.id_prest).subscribe({
      next: () => this.load(),
      error: (e) => this.error.set(e.error?.message ?? 'Error.'),
    });
  }

  espNombre(codigo: string): string {
    return this.especialidades().find(e => e.codigo_esp === codigo)?.nombre ?? codigo;
  }
}
