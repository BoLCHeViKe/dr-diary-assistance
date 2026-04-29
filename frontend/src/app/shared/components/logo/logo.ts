import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-logo',
  templateUrl: './logo.html',
  styleUrl: './logo.scss',
})
export class LogoComponent {
  @Input() size: 'normal' | 'sm' = 'normal';
}
