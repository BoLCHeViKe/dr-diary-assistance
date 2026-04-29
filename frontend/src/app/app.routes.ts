import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';

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
        loadComponent: () => import('./features/agenda/agenda').then(m => m.AgendaComponent)
      },
      {
        path: 'pacientes',
        loadComponent: () => import('./features/construccion/construccion').then(m => m.ConstruccionComponent)
      },
      {
        path: 'facturacion',
        loadComponent: () => import('./features/construccion/construccion').then(m => m.ConstruccionComponent)
      },
      {
        path: 'gestion',
        loadComponent: () => import('./features/construccion/construccion').then(m => m.ConstruccionComponent)
      },
    ]
  },
  { path: '**', redirectTo: '' }
];
