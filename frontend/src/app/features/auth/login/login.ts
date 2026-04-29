import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule],
  templateUrl: './login.html',
})
export class LoginComponent {
  private readonly fb     = inject(FormBuilder);
  private readonly auth   = inject(AuthService);
  private readonly router = inject(Router);

  form = this.fb.nonNullable.group({
    email:    ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  loading = signal(false);
  error   = signal('');
  showPwd = signal(false);

  submit() {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.loading.set(true);
    this.error.set('');

    const { email, password } = this.form.getRawValue();
    this.auth.login(email, password).subscribe({
      next: ()  => this.router.navigate(['/']),
      error: (e) => {
        this.error.set(e.error?.message ?? 'Error de conexión. Inténtelo de nuevo.');
        this.loading.set(false);
      }
    });
  }

  field(name: 'email' | 'password') { return this.form.get(name)!; }
  invalid(name: 'email' | 'password') {
    return this.field(name).invalid && this.field(name).touched;
  }
}
