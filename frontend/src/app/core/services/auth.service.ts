import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap } from 'rxjs';

export interface RolPermisos {
  id: number;
  tipo: string;
  insignia?: string;
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

export type PermKey = keyof Omit<RolPermisos, 'id' | 'tipo'>;

export interface AuthUser {
  id: number;
  nombre: string;
  apellido1: string;
  apellido2?: string;
  email: string;
  dni: string;
  activo: boolean;
  id_rol: number;
  rol: RolPermisos;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http   = inject(HttpClient);
  private readonly router = inject(Router);

  private readonly TOKEN_KEY = 'dda_token';
  private readonly USER_KEY  = 'dda_user';

  login(email: string, password: string): Observable<{ token: string; user: AuthUser }> {
    return this.http.post<{ token: string; user: AuthUser }>('/api/login', { email, password }).pipe(
      tap(res => {
        localStorage.setItem(this.TOKEN_KEY, res.token);
        localStorage.setItem(this.USER_KEY, JSON.stringify(res.user));
      })
    );
  }

  logout(): void {
    this.http.post('/api/logout', {}).subscribe();
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
    this.router.navigate(['/login']);
  }

  getToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  getUser(): AuthUser | null {
    const raw = localStorage.getItem(this.USER_KEY);
    return raw ? JSON.parse(raw) : null;
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  hasPermission(perm: PermKey): boolean {
    const user = this.getUser();
    if (!user?.rol) return false;
    // ADMIN siempre tiene todo
    if (user.rol.id === 1) return true;
    return !!user.rol[perm];
  }

  isAdmin(): boolean {
    return this.getUser()?.id_rol === 1;
  }

  isMedico(): boolean {
    return this.getUser()?.id_rol === 2;
  }
}
