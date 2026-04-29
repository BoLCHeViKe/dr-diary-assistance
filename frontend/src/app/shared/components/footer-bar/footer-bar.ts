import { Component, OnDestroy, OnInit, signal } from '@angular/core';

@Component({
  selector: 'app-footer-bar',
  template: `<div class="footer-bar text-center text-lg-end">{{ now() }}</div>`
})
export class FooterBarComponent implements OnInit, OnDestroy {
  now = signal('');
  private intervalId: any;

  ngOnInit() {
    this.tick();
    this.intervalId = setInterval(() => this.tick(), 1000);
  }

  ngOnDestroy() {
    clearInterval(this.intervalId);
  }

  private tick() {
    const d = new Date();
    this.now.set(
      d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) +
      ' horas del ' +
      d.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' })
    );
  }
}
