import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-gestion-landing',
  imports: [RouterLink],
  templateUrl: './gestion-landing.html',
  styleUrl: './gestion-landing.scss',
})
export class GestionLandingComponent {
  readonly auth = inject(AuthService);
}
