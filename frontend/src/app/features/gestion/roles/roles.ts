import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { GestionService, Rol } from '../../../core/services/gestion.service';

type PanelMode = null | 'create' | 'edit';

const PERM_GROUPS = [
  {
    label: 'Citas',
    perms: [
      { key: 'perm_agenda',       label: 'Acceso Agenda' },
      { key: 'perm_hc',           label: 'Acceso H.C.' },
      { key: 'perm_multi_agenda', label: 'Acceso a múltiples Agendas' },
    ],
  },
  {
    label: 'Administración',
    perms: [
      { key: 'perm_facturacion',  label: 'Acceso Facturación' },
      { key: 'perm_estadisticas', label: 'Acceso Estadísticas' },
    ],
  },
  {
    label: 'Gestión',
    perms: [
      { key: 'perm_gest_roles',          label: 'Gestión Roles' },
      { key: 'perm_gest_usuarios',       label: 'Gestión Usuarios' },
      { key: 'perm_gest_prestaciones',   label: 'Gestión Prestaciones' },
      { key: 'perm_gest_especialidades', label: 'Gestión Especialidades' },
    ],
  },
];

@Component({
  selector: 'app-gestion-roles',
  imports: [ReactiveFormsModule],
  templateUrl: './roles.html',
  styleUrl: './roles.scss',
})
export class GestionRolesComponent implements OnInit {
  private readonly svc = inject(GestionService);
  private readonly fb  = inject(FormBuilder);

  readonly permGroups = PERM_GROUPS;

  roles      = signal<Rol[]>([]);
  panelMode  = signal<PanelMode>(null);
  editingRol = signal<Rol | null>(null);
  saving     = signal(false);
  error      = signal('');
  formError  = signal('');

  form = this.fb.nonNullable.group({
    tipo:                     ['', [Validators.required, Validators.maxLength(20)]],
    perm_agenda:              [false],
    perm_hc:                  [false],
    perm_multi_agenda:        [false],
    perm_facturacion:         [false],
    perm_estadisticas:        [false],
    perm_gest_roles:          [false],
    perm_gest_usuarios:       [false],
    perm_gest_prestaciones:   [false],
    perm_gest_especialidades: [false],
  });

  ngOnInit() { this.load(); }

  load() {
    this.svc.getRoles().subscribe({
      next: (list) => this.roles.set(list),
      error: () => this.error.set('Error al cargar los roles.'),
    });
  }

  openCreate() {
    this.form.reset();
    this.editingRol.set(null);
    this.formError.set('');
    this.panelMode.set('create');
  }

  openEdit(rol: Rol) {
    if (rol.id === 1) return; // ADMIN bloqueado
    this.editingRol.set(rol);
    this.form.patchValue(rol as any);
    // Para MEDICO, bloquear los permisos mínimos
    if (rol.id === 2) {
      this.form.get('perm_agenda')!.disable();
      this.form.get('perm_hc')!.disable();
      this.form.get('perm_facturacion')!.disable();
    } else {
      ['perm_agenda','perm_hc','perm_facturacion'].forEach(p => this.form.get(p)!.enable());
    }
    this.formError.set('');
    this.panelMode.set('edit');
  }

  closePanel() {
    this.panelMode.set(null);
    this.editingRol.set(null);
    this.form.reset();
    ['perm_agenda','perm_hc','perm_facturacion'].forEach(p => this.form.get(p)!.enable());
  }

  submit() {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const data = this.form.getRawValue();

    const obs = this.panelMode() === 'create'
      ? this.svc.createRol(data)
      : this.svc.updateRol(this.editingRol()!.id, data);

    obs.subscribe({
      next: () => { this.saving.set(false); this.closePanel(); this.load(); },
      error: (e) => { this.formError.set(e.error?.error ?? e.error?.message ?? 'Error.'); this.saving.set(false); },
    });
  }

  delete(rol: Rol) {
    if (!confirm(`¿Eliminar el rol "${rol.tipo}"? Esta acción es irreversible.`)) return;
    this.svc.deleteRol(rol.id).subscribe({
      next: () => this.load(),
      error: (e) => this.error.set(e.error?.message ?? 'Error al eliminar el rol.'),
    });
  }

  isProtected(rol: Rol) { return rol.id === 1 || rol.id === 2; }

  permCount(rol: Rol): number {
    return PERM_GROUPS.flatMap(g => g.perms).filter(p => (rol as any)[p.key]).length;
  }
}
