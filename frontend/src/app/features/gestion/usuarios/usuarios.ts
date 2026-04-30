import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { GestionService, GestionUser, Rol } from '../../../core/services/gestion.service';

type PanelMode = null | 'create' | 'edit';

@Component({
  selector: 'app-gestion-usuarios',
  imports: [ReactiveFormsModule],
  templateUrl: './usuarios.html',
  styleUrl: './usuarios.scss',
})
export class GestionUsuariosComponent implements OnInit {
  private readonly svc = inject(GestionService);
  private readonly fb  = inject(FormBuilder);

  usuarios   = signal<GestionUser[]>([]);
  roles      = signal<Rol[]>([]);
  panelMode  = signal<PanelMode>(null);
  editingUser = signal<GestionUser | null>(null);
  saving     = signal(false);
  error      = signal('');
  formError  = signal('');

  form = this.fb.nonNullable.group({
    nombre:    ['', [Validators.required, Validators.maxLength(30)]],
    apellido1: ['', [Validators.required, Validators.maxLength(30)]],
    apellido2: ['', Validators.maxLength(30)],
    email:     ['', [Validators.required, Validators.email]],
    password:  ['', Validators.minLength(8)],
    dni:       ['', [Validators.pattern(/^\d{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i)]],
    fecha_nac: [''],
    telf:      ['', Validators.maxLength(13)],
    direccion: ['', Validators.maxLength(100)],
    id_rol:    [2, Validators.required],
    num_col:   [''],
    num_auto:  [''],
  });

  ngOnInit() {
    this.load();
    this.svc.getRoles().subscribe(list => this.roles.set(list));
  }

  load() {
    this.svc.getUsuarios(true).subscribe({
      next: (list) => this.usuarios.set(list),
      error: () => this.error.set('Error al cargar los usuarios.'),
    });
  }

  openCreate() {
    this.form.reset({ id_rol: 2 });
    this.form.get('password')!.setValidators([Validators.required, Validators.minLength(8)]);
    this.form.get('password')!.updateValueAndValidity();
    this.editingUser.set(null);
    this.formError.set('');
    this.panelMode.set('create');
  }

  openEdit(u: GestionUser) {
    this.editingUser.set(u);
    this.form.patchValue({
      nombre: u.nombre, apellido1: u.apellido1, apellido2: u.apellido2 ?? '',
      email: u.email, dni: u.dni, fecha_nac: u.fecha_nac ?? '',
      telf: u.telf ?? '', direccion: u.direccion ?? '',
      id_rol: u.id_rol,
      num_col: u.medico?.num_col ?? '',
      num_auto: u.admin?.num_auto ?? '',
    });
    // Password no obligatoria en edición
    this.form.get('password')!.setValidators(Validators.minLength(8));
    this.form.get('password')!.updateValueAndValidity();
    this.formError.set('');
    this.panelMode.set('edit');
  }

  closePanel() {
    this.panelMode.set(null);
    this.editingUser.set(null);
    this.form.reset({ id_rol: 2 });
  }

  submit() {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.getRawValue();
    const data: any = {
      nombre: raw.nombre, apellido1: raw.apellido1, apellido2: raw.apellido2 || null,
      email: raw.email, dni: raw.dni || undefined, fecha_nac: raw.fecha_nac || null,
      telf: raw.telf || null, direccion: raw.direccion || null, id_rol: raw.id_rol,
    };
    if (raw.password) data['password'] = raw.password;
    if (raw.id_rol == 2 && raw.num_col) data['num_col'] = raw.num_col;
    if (raw.id_rol == 1 && raw.num_auto) data['num_auto'] = raw.num_auto;

    const obs = this.panelMode() === 'create'
      ? this.svc.createUsuario(data)
      : this.svc.updateUsuario(this.editingUser()!.id, data);

    obs.subscribe({
      next: () => { this.saving.set(false); this.closePanel(); this.load(); },
      error: (e) => { this.formError.set(e.error?.error ?? e.error?.message ?? 'Error.'); this.saving.set(false); },
    });
  }

  toggleActivo(u: GestionUser) {
    const accion = u.activo ? 'deshabilitar' : 'habilitar';
    if (!confirm(`¿${accion} a ${u.nombre} ${u.apellido1}?`)) return;
    this.svc.toggleUsuario(u.id, !u.activo).subscribe({
      next: () => this.load(),
      error: (e) => this.error.set(e.error?.error ?? 'Error al cambiar el estado.'),
    });
  }

  rolNombre(idRol: number): string {
    return this.roles().find(r => r.id === idRol)?.tipo ?? '—';
  }
}
