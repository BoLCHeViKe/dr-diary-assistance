import { Component, EventEmitter, Input, OnInit, Output, computed, inject, signal } from '@angular/core';
import { PacienteService, Paciente } from '../../../core/services/paciente.service';

@Component({
  selector: 'app-paciente-selector',
  templateUrl: './paciente-selector.html',
  styleUrl:    './paciente-selector.scss',
})
export class PacienteSelectorComponent implements OnInit {
  private readonly svc = inject(PacienteService);

  @Input() mode: 'page' | 'inline' = 'page';

  // Trigger a reload when parent increments this value (used after CRUD)
  @Input() set refreshTrigger(v: number) { if (v > 0) this.loadPacientes(); }

  @Output() seleccionado = new EventEmitter<Paciente>(); // single click
  @Output() abierto      = new EventEmitter<Paciente>(); // double click (page mode → edit)

  // ── State ─────────────────────────────────────────────────────────────────
  allPacientes = signal<Paciente[]>([]);
  loading      = signal(false);
  activeRow    = signal<Paciente | null>(null);

  // ── Search fields (signals for live computed filtering) ───────────────────
  ap1  = signal('');
  ap2  = signal('');
  nom  = signal('');
  dni  = signal('');
  telf = signal('');

  // ── Computed ──────────────────────────────────────────────────────────────
  hasQuery = computed(() => !!(this.ap1() || this.ap2() || this.nom() || this.dni() || this.telf()));

  resultados = computed(() => {
    if (!this.hasQuery()) return [];
    const [ap1, ap2, nom, dni, telf] = [this.ap1(), this.ap2(), this.nom(), this.dni(), this.telf()];
    return this.allPacientes().filter(p =>
      this.sw(p.apellido1,      ap1) &&
      this.sw(p.apellido2 ?? '', ap2) &&
      this.sw(p.nombre,          nom) &&
      this.sw(p.dni,             dni) &&
      this.sw(p.telf ?? '',      telf)
    );
  });

  // ── Lifecycle ─────────────────────────────────────────────────────────────
  ngOnInit() { this.loadPacientes(); }

  loadPacientes() {
    this.loading.set(true);
    this.svc.getAll().subscribe({
      next: (list) => { this.allPacientes.set(list); this.loading.set(false); },
      error: ()    => { this.loading.set(false); },
    });
  }

  // ── Interactions ──────────────────────────────────────────────────────────
  clickRow(p: Paciente) {
    this.activeRow.set(p);
    this.seleccionado.emit(p);
  }

  dblClickRow(p: Paciente) {
    this.activeRow.set(p);
    this.seleccionado.emit(p);
    if (this.mode === 'page') this.abierto.emit(p);
  }

  clear() {
    this.ap1.set(''); this.ap2.set(''); this.nom.set('');
    this.dni.set(''); this.telf.set('');
    this.activeRow.set(null);
  }

  onInput(field: 'ap1' | 'ap2' | 'nom' | 'dni' | 'telf', event: Event) {
    const val = (event.target as HTMLInputElement).value;
    const map: Record<typeof field, (v: string) => void> = {
      ap1:  (v) => this.ap1.set(v),
      ap2:  (v) => this.ap2.set(v),
      nom:  (v) => this.nom.set(v),
      dni:  (v) => this.dni.set(v),
      telf: (v) => this.telf.set(v),
    };
    map[field](val);
    this.activeRow.set(null);
  }

  isActive(p: Paciente): boolean {
    return this.activeRow()?.id_paciente === p.id_paciente;
  }

  // ── Search helpers (accent + case insensitive, starts-with) ──────────────
  private norm(s: string): string {
    return s.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim();
  }

  private sw(field: string, query: string): boolean {
    return !query || this.norm(field).startsWith(this.norm(query));
  }
}
