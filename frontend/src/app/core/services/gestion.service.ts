import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { RolPermisos } from './auth.service';

export interface Rol {
  id: number;
  tipo: string;
  insignia?: string;
  usuarios_count?: number;
  perm_agenda: boolean;
  perm_hc: boolean;
  perm_agenda_disponible: boolean;
  perm_multi_agenda: boolean;
  perm_facturacion: boolean;
  perm_estadisticas: boolean;
  perm_gest_roles: boolean;
  perm_gest_usuarios: boolean;
  perm_gest_prestaciones: boolean;
  perm_gest_especialidades: boolean;
}

export interface GestionUser {
  id: number;
  nombre: string;
  apellido1: string;
  apellido2?: string;
  email: string;
  dni: string;
  fecha_nac?: string;
  telf?: string;
  direccion?: string;
  id_rol: number;
  activo: boolean;
  rol?: RolPermisos;
  medico?: { id: number; num_col: string };
  admin?: { id: number; num_auto: string };
}

export interface GestionEspecialidad {
  codigo_esp: string;
  nombre: string;
  activo: boolean;
  prestaciones?: GestionPrestacion[];
}

export interface GestionPrestacion {
  codigo_esp: string;
  id_prest: number;
  nombre: string;
  descripcion?: string;
  precio: number;
  activo: boolean;
  especialidad?: { codigo_esp: string; nombre: string };
}

@Injectable({ providedIn: 'root' })
export class GestionService {
  private readonly http = inject(HttpClient);

  // ── Roles ──────────────────────────────────────────────────────────────────
  getRoles()                       { return this.http.get<Rol[]>('/api/roles'); }
  createRol(data: Partial<Rol>)    { return this.http.post<Rol>('/api/roles', data); }
  updateRol(id: number, data: Partial<Rol>) { return this.http.put<Rol>(`/api/roles/${id}`, data); }
  deleteRol(id: number)            { return this.http.delete<{ message: string }>(`/api/roles/${id}`); }

  // ── Usuarios ───────────────────────────────────────────────────────────────
  getUsuarios(todos = true) {
    const params = todos ? new HttpParams().set('todos', '1') : new HttpParams();
    return this.http.get<GestionUser[]>('/api/usuarios', { params });
  }
  createUsuario(data: any)              { return this.http.post<GestionUser>('/api/usuarios', data); }
  updateUsuario(id: number, data: any)  { return this.http.put<GestionUser>(`/api/usuarios/${id}`, data); }
  toggleUsuario(id: number, activo: boolean) {
    return this.http.put<GestionUser>(`/api/usuarios/${id}`, { activo });
  }

  // ── Especialidades ─────────────────────────────────────────────────────────
  getEspecialidades() {
    return this.http.get<GestionEspecialidad[]>('/api/especialidades', {
      params: new HttpParams().set('gestion', '1'),
    });
  }
  createEspecialidad(data: { codigo_esp: string; nombre: string }) {
    return this.http.post<GestionEspecialidad>('/api/especialidades', data);
  }
  updateEspecialidad(codigo: string, data: { nombre?: string }) {
    return this.http.put<GestionEspecialidad>(`/api/especialidades/${codigo}`, data);
  }
  toggleEspecialidad(codigo: string) {
    return this.http.patch<{ message: string; especialidad: GestionEspecialidad }>(
      `/api/especialidades/${codigo}/toggle`, {}
    );
  }

  // ── Prestaciones ───────────────────────────────────────────────────────────
  getPrestaciones() {
    return this.http.get<GestionPrestacion[]>('/api/prestaciones', {
      params: new HttpParams().set('gestion', '1'),
    });
  }
  createPrestacion(data: { codigo_esp: string; nombre: string; descripcion?: string; precio: number }) {
    return this.http.post<GestionPrestacion>('/api/prestaciones', data);
  }
  updatePrestacion(codigo: string, idPrest: number, data: { nombre?: string; descripcion?: string; precio?: number }) {
    return this.http.put<GestionPrestacion>(`/api/prestaciones/${codigo}/${idPrest}`, data);
  }
  togglePrestacion(codigo: string, idPrest: number) {
    return this.http.patch<{ message: string; prestacion: GestionPrestacion }>(
      `/api/prestaciones/${codigo}/${idPrest}/toggle`, {}
    );
  }
}
