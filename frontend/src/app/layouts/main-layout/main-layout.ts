import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { NavbarComponent } from '../../shared/components/navbar/navbar';
import { FooterBarComponent } from '../../shared/components/footer-bar/footer-bar';

@Component({
  selector: 'app-main-layout',
  imports: [RouterOutlet, NavbarComponent, FooterBarComponent],
  template: `
    <app-navbar />
    <main class="main-content container-fluid">
      <router-outlet />
    </main>
    <app-footer-bar />
  `
})
export class MainLayoutComponent {}
