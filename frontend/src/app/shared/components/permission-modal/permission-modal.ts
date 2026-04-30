import { Component, inject } from '@angular/core';
import { PermissionModalService } from './permission-modal.service';

@Component({
  selector: 'app-permission-modal',
  template: `
    @if (svc.visible()) {
      <div class="modal-backdrop fade show" (click)="svc.hide()"></div>
      <div class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
              <h5 class="modal-title text-danger">
                <i class="bi bi-shield-lock-fill me-2"></i>Acceso restringido
              </h5>
              <button type="button" class="btn-close" (click)="svc.hide()"></button>
            </div>
            <div class="modal-body pt-2 pb-4 text-center">
              <i class="bi bi-lock-fill text-secondary" style="font-size:2.5rem"></i>
              <p class="mt-3 mb-0">
                No tiene permisos para esta funcionalidad.<br>
                <span class="text-muted small">Contacte con el administrador del sistema.</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class PermissionModalComponent {
  readonly svc = inject(PermissionModalService);
}
