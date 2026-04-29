import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-home',
  imports: [RouterLink],
  templateUrl: './home.html',
  styleUrl: './home.scss',
})
export class HomeComponent {
  private readonly auth = inject(AuthService);

  get saludo(): string {
    const u = this.auth.getUser();
    if (!u) return '';
    const apellido = (u.apellido1 ?? '').toUpperCase();
    const rol = (u.rol?.tipo ?? '').toLowerCase();
    const prefijo = rol === 'admin' ? 'Admin' : 'DR/a.';
    return `¡Hola ${prefijo} ${apellido}!`;
  }
}
