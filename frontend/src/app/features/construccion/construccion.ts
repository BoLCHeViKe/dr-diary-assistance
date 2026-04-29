import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-construccion',
  imports: [RouterLink],
  template: `
    <div class="container py-5 text-center">
      <i class="bi bi-tools" style="font-size:4rem;color:#0092E0;"></i>
      <h3 class="mt-3 fw-bold" style="color:#04078B;">Sección en construcción</h3>
      <p class="text-muted mb-4">Esta funcionalidad se implementará en el próximo sprint.</p>
      <a routerLink="/" class="btn btn-primary">
        <i class="bi bi-house me-2"></i>Volver al inicio
      </a>
    </div>
  `
})
export class ConstruccionComponent {}
