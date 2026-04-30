import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { permissionGuard } from './core/guards/permission.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login').then(m => m.LoginComponent)
  },
  {
    path: '',
    loadComponent: () => import('./layouts/main-layout/main-layout').then(m => m.MainLayoutComponent),
    canActivate: [authGuard],
    children: [
      {
        path: '',
        loadComponent: () => import('./features/home/home').then(m => m.HomeComponent)
      },
      {
        path: 'agenda',
        loadComponent: () => import('./features/agenda/agenda').then(m => m.AgendaComponent),
        canActivate: [permissionGuard('perm_agenda')],
      },
      {
        path: 'pacientes',
        loadComponent: () => import('./features/pacientes/pacientes').then(m => m.PacientesComponent)
        // Pacientes siempre accesible — sin guard
      },
      {
        path: 'facturacion',
        loadComponent: () => import('./features/facturacion/facturacion').then(m => m.FacturacionComponent),
        canActivate: [permissionGuard('perm_facturacion')],
      },
      {
        path: 'gestion',
        loadComponent: () => import('./features/gestion/gestion').then(m => m.GestionComponent),
        children: [
          {
            path: '',
            loadComponent: () => import('./features/gestion/gestion-landing').then(m => m.GestionLandingComponent),
            canActivate: [permissionGuard('perm_gest_roles')],
          },
          {
            path: 'roles',
            loadComponent: () => import('./features/gestion/roles/roles').then(m => m.GestionRolesComponent),
            canActivate: [permissionGuard('perm_gest_roles')],
          },
          {
            path: 'usuarios',
            loadComponent: () => import('./features/gestion/usuarios/usuarios').then(m => m.GestionUsuariosComponent),
            canActivate: [permissionGuard('perm_gest_usuarios')],
          },
          {
            path: 'especialidades',
            loadComponent: () => import('./features/gestion/especialidades/especialidades').then(m => m.GestionEspecialidadesComponent),
            canActivate: [permissionGuard('perm_gest_especialidades')],
          },
          {
            path: 'prestaciones',
            loadComponent: () => import('./features/gestion/prestaciones/prestaciones').then(m => m.GestionPrestacionesComponent),
            canActivate: [permissionGuard('perm_gest_prestaciones')],
          },
        ],
      },
    ]
  },
  { path: '**', redirectTo: '' }
];
