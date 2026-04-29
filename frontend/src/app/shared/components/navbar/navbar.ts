import { Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-navbar',
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './navbar.html',
})
export class NavbarComponent {
  private readonly auth = inject(AuthService);

  get user() { return this.auth.getUser(); }

  get nombreMostrado(): string {
    const u = this.user;
    return u ? `${u.apellido1 ?? ''}`.toUpperCase() : '';
  }

  logout() { this.auth.logout(); }
}
