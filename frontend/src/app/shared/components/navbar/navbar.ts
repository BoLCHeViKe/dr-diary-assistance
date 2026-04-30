import { Component, ElementRef, HostListener, inject } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-navbar',
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './navbar.html',
})
export class NavbarComponent {
  private readonly auth = inject(AuthService);
  private readonly el   = inject(ElementRef);

  get user() { return this.auth.getUser(); }

  get nombreMostrado(): string {
    const u = this.user;
    return u ? `${u.apellido1 ?? ''}`.toUpperCase() : '';
  }

  logout() { this.auth.logout(); }

  closeCollapse() {
    const el = document.getElementById('navbarMain');
    if (!el?.classList.contains('show')) return;
    const bs = (window as any)['bootstrap'];
    if (bs?.Collapse) {
      (bs.Collapse.getInstance(el) ?? new bs.Collapse(el, { toggle: false })).hide();
    }
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: Event) {
    if (!this.el.nativeElement.contains(event.target as Node)) {
      this.closeCollapse();
    }
  }
}
