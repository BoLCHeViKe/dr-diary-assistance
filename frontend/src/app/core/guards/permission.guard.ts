import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService, PermKey } from '../services/auth.service';
import { PermissionModalService } from '../../shared/components/permission-modal/permission-modal.service';

export function permissionGuard(perm: PermKey): CanActivateFn {
  return () => {
    const auth  = inject(AuthService);
    const modal = inject(PermissionModalService);
    const router = inject(Router);

    if (auth.hasPermission(perm)) return true;

    modal.show();
    // Redirige a home para no dejar la URL en estado inválido
    return router.createUrlTree(['/']);
  };
}
