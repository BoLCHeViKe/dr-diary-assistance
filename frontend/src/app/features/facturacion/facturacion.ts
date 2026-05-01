import { Component, HostListener, OnInit, computed, inject, signal } from '@angular/core';
import { CurrencyPipe, DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FacturaService, Factura, FacturaFilters } from '../../core/services/factura.service';
import { PacienteService, Paciente } from '../../core/services/paciente.service';
import { GestionService, GestionEspecialidad } from '../../core/services/gestion.service';

const ESTADOS_FACTURA = ['borrador', 'emitida', 'anulada', 'abono'] as const;
type EstadoFact = typeof ESTADOS_FACTURA[number];

type SortCol    = 'num_fact' | 'fecha' | 'paciente' | 'importe' | 'abonado' | 'estado';
type SortDir    = 'asc' | 'desc';
type ActionStep = 'idle' | 'delete-confirm' | 'abono-input' | 'abono-confirm' | 'anular-confirm';

const DEFAULT_COL_ORDER = ['num_fact','fecha','paciente','especialidad','importe','abonado','estado','fact_ref'] as const;
type ColKey = typeof DEFAULT_COL_ORDER[number];

@Component({
  selector: 'app-facturacion',
  imports: [CurrencyPipe, DatePipe, FormsModule],
  templateUrl: './facturacion.html',
  styleUrl: './facturacion.scss',
})
export class FacturacionComponent implements OnInit {
  private readonly svc        = inject(FacturaService);
  private readonly pacSvc     = inject(PacienteService);
  private readonly gestionSvc = inject(GestionService);

  // ── State ─────────────────────────────────────────────────────────────────
  facturas    = signal<Factura[]>([]);
  totalItems  = signal(0);
  totales     = signal({ facturado: 0, abonos: 0, neto: 0 });
  loading     = signal(false);
  error       = signal('');
  currentPage = signal(1);

  // ── Row interaction ────────────────────────────────────────────────────────
  selectedRow  = signal<number | null>(null);
  expandedRow  = signal<number | null>(null);
  actionStep   = signal<ActionStep>('idle');
  abonoInput   = signal('');
  actionError  = signal('');

  // ── Sorting ───────────────────────────────────────────────────────────────
  sortCol = signal<SortCol>('num_fact');
  sortDir = signal<SortDir>('desc');

  sortedFacturas = computed(() => {
    const col = this.sortCol();
    const dir = this.sortDir();
    const list = [...this.facturas()];
    list.sort((a, b) => {
      let va: string | number = 0;
      let vb: string | number = 0;
      if (col === 'num_fact') { va = a.num_fact; vb = b.num_fact; }
      else if (col === 'fecha') { va = a.fecha; vb = b.fecha; }
      else if (col === 'paciente') {
        va = `${a.paciente?.apellido1 ?? ''} ${a.paciente?.nombre ?? ''}`;
        vb = `${b.paciente?.apellido1 ?? ''} ${b.paciente?.nombre ?? ''}`;
      }
      else if (col === 'importe') { va = this.importe(a); vb = this.importe(b); }
      else if (col === 'abonado') { va = this.abonado(a); vb = this.abonado(b); }
      else if (col === 'estado') { va = a.estado; vb = b.estado; }
      if (va < vb) return dir === 'asc' ? -1 : 1;
      if (va > vb) return dir === 'asc' ? 1 : -1;
      return 0;
    });
    return list;
  });

  sort(col: SortCol) {
    if (this.sortCol() === col) {
      this.sortDir.update(d => d === 'asc' ? 'desc' : 'asc');
    } else {
      this.sortCol.set(col);
      this.sortDir.set(col === 'num_fact' || col === 'fecha' ? 'desc' : 'asc');
    }
  }

  sortIcon(col: SortCol): string {
    if (this.sortCol() !== col) return 'bi-arrow-down-up';
    return this.sortDir() === 'asc' ? 'bi-sort-up' : 'bi-sort-down';
  }

  // ── Patient search for filter ──────────────────────────────────────────────
  allPacientes        = signal<Paciente[]>([]);
  patientQuery        = signal('');
  selectedPatient     = signal<Paciente | null>(null);
  showPatientDropdown = signal(false);

  filteredPacientes = computed(() => {
    const q = this.patientQuery().toLowerCase().trim();
    if (!q || this.selectedPatient()) return [];
    return this.allPacientes().filter(p =>
      `${p.apellido1} ${p.apellido2 ?? ''} ${p.nombre}`.toLowerCase().includes(q) ||
      p.dni.toLowerCase().startsWith(q)
    ).slice(0, 12);
  });

  // ── Especialidades filter ─────────────────────────────────────────────────
  especialidades  = signal<GestionEspecialidad[]>([]);
  espFilter       = signal<string[]>(['TODAS']);
  espDropdownOpen = signal(false);

  hasDisabledEsps = computed(() => this.especialidades().some(e => !e.activo));
  todasEsp        = computed(() => this.espFilter().includes('TODAS'));

  espLabel = computed(() => {
    if (this.todasEsp()) return 'TODAS';
    return this.espFilter().map(c => {
      if (c === '__disabled__') return 'Esp. deshabilitadas';
      return this.especialidades().find(e => e.codigo_esp === c)?.nombre ?? c;
    }).join(', ');
  });

  toggleEspTodas() { this.espFilter.set(['TODAS']); }

  toggleEspItem(codigo: string) {
    const current = this.espFilter().filter(v => v !== 'TODAS');
    const idx = current.indexOf(codigo);
    if (idx >= 0) current.splice(idx, 1); else current.push(codigo);
    this.espFilter.set(current.length > 0 ? current : ['TODAS']);
  }

  // ── Estado filter ─────────────────────────────────────────────────────────
  estadoFilter       = signal<string[]>(['TODAS']);
  estadoDropdownOpen = signal(false);

  todasEstados = computed(() => this.estadoFilter().includes('TODAS'));

  estadoLabel = computed(() => {
    if (this.todasEstados()) return 'TODAS';
    return this.estadoFilter().map((e: string) => this.ESTADO_LABELS[e as EstadoFact]).join(', ');
  });

  toggleEstadoTodas() { this.estadoFilter.set(['TODAS']); }

  toggleEstadoItem(est: string) {
    const current = this.estadoFilter().filter((v: string) => v !== 'TODAS');
    const idx = current.indexOf(est);
    if (idx >= 0) current.splice(idx, 1); else current.push(est);
    this.estadoFilter.set(current.length > 0 ? current : ['TODAS']);
  }

  especialidadDeFactura(f: Factura): string {
    const codigo = f.lineas?.[0]?.codigo_esp;
    if (!codigo) return '—';
    const esp = this.especialidades().find(e => e.codigo_esp === codigo);
    return esp ? esp.nombre : codigo;
  }

  // ── Column drag & drop ────────────────────────────────────────────────────
  columnOrder    = signal<ColKey[]>([...DEFAULT_COL_ORDER]);
  draggedColIdx  = signal<number | null>(null);

  onColDragStart(index: number) { this.draggedColIdx.set(index); }

  onColDragOver(event: DragEvent) { event.preventDefault(); }

  onColDrop(targetIndex: number) {
    const fromIdx = this.draggedColIdx();
    if (fromIdx === null || fromIdx === targetIndex) { this.draggedColIdx.set(null); return; }
    const cols = [...this.columnOrder()];
    const [moved] = cols.splice(fromIdx, 1);
    cols.splice(targetIndex, 0, moved);
    this.columnOrder.set(cols);
    this.draggedColIdx.set(null);
  }

  onColDragEnd() { this.draggedColIdx.set(null); }

  readonly COL_LABELS: Record<ColKey, string> = {
    num_fact:     'Nº Factura',
    fecha:        'Fecha',
    paciente:     'Paciente',
    especialidad: 'Especialidad',
    importe:      'Importe',
    abonado:      'Abonado',
    estado:       'Estado',
    fact_ref:     'Fact. Rectif.',
  };

  isSortableCol(col: ColKey): col is SortCol {
    return ['num_fact','fecha','paciente','importe','abonado','estado'].includes(col);
  }

  colIsNumeric(col: ColKey): boolean {
    return col === 'importe' || col === 'abonado';
  }

  // ── Filters ───────────────────────────────────────────────────────────────
  readonly ESTADOS = ESTADOS_FACTURA;
  readonly ESTADO_LABELS: Record<EstadoFact, string> = {
    borrador: 'Borrador', emitida: 'Emitida', anulada: 'Anulada', abono: 'Abono',
  };

  desde   = signal('');
  hasta   = signal('');

  readonly PER_PAGE = 10;

  totalPages = computed(() => Math.max(1, Math.ceil(this.totalItems() / this.PER_PAGE)));

  // ── Lifecycle ─────────────────────────────────────────────────────────────
  ngOnInit() {
    this.setDefaultDates();
    this.load();
    this.pacSvc.getAll().subscribe(list => this.allPacientes.set(list));
    this.gestionSvc.getEspecialidades().subscribe(list => this.especialidades.set(list));
  }

  private setDefaultDates() {
    const now   = new Date();
    const y     = now.getFullYear();
    const m     = String(now.getMonth() + 1).padStart(2, '0');
    const today = `${y}-${m}-${String(now.getDate()).padStart(2, '0')}`;
    this.desde.set(`${y}-${m}-01`);
    this.hasta.set(today);
  }

  // ── Load ──────────────────────────────────────────────────────────────────
  load(page = 1) {
    this.loading.set(true);
    this.error.set('');
    this.currentPage.set(page);

    const filters: FacturaFilters = { page };
    if (this.desde()) filters.desde_fecha = this.desde();
    if (this.hasta()) filters.hasta_fecha = this.hasta();
    if (this.selectedPatient()) filters.id_paciente = this.selectedPatient()!.id_paciente;
    const estArr = this.estadoFilter();
    if (!estArr.includes('TODAS')) filters.estados = estArr.join(',');
    const espArr = this.espFilter();
    if (!espArr.includes('TODAS')) filters.especialidades = espArr.join(',');

    this.svc.getFacturas(filters).subscribe({
      next: (res) => {
        this.facturas.set(res.facturas);
        this.totalItems.set(res.total_items);
        this.totales.set(res.totales);
        this.loading.set(false);
      },
      error: () => { this.error.set('Error al cargar las facturas.'); this.loading.set(false); },
    });
  }

  search() { this.load(1); }

  resetFilters() {
    this.setDefaultDates();
    this.estadoFilter.set(['TODAS']);
    this.selectedPatient.set(null);
    this.patientQuery.set('');
    this.espFilter.set(['TODAS']);
    this.espDropdownOpen.set(false);
    this.estadoDropdownOpen.set(false);
    this.columnOrder.set([...DEFAULT_COL_ORDER]);
    this.load(1);
  }

  // ── Pagination ────────────────────────────────────────────────────────────
  goPage(p: number) { if (p >= 1 && p <= this.totalPages()) this.load(p); }

  pages = computed(() => {
    const total = this.totalPages();
    const cur   = this.currentPage();
    const delta = 2;
    const range: (number | '...')[] = [];
    let prev = 0;
    for (let i = 1; i <= total; i++) {
      if (i === 1 || i === total || (i >= cur - delta && i <= cur + delta)) {
        if (prev && i - prev > 1) range.push('...');
        range.push(i);
        prev = i;
      }
    }
    return range;
  });

  // ── Estado filter toggle — handled by toggleEstadoTodas / toggleEstadoItem ──

  @HostListener('document:click')
  onDocClick() {
    this.espDropdownOpen.set(false);
    this.estadoDropdownOpen.set(false);
  }

  // ── Patient search in filter ──────────────────────────────────────────────
  onPatientInput(event: Event) {
    this.patientQuery.set((event.target as HTMLInputElement).value);
    this.selectedPatient.set(null);
    this.showPatientDropdown.set(true);
  }

  selectPatient(p: Paciente) {
    this.selectedPatient.set(p);
    this.patientQuery.set(`${p.apellido1} ${p.apellido2 ?? ''}, ${p.nombre}`.trim());
    this.showPatientDropdown.set(false);
  }

  clearPatient() {
    this.selectedPatient.set(null);
    this.patientQuery.set('');
  }

  // ── Per-invoice computed values ───────────────────────────────────────────
  importe(f: Factura): number {
    if (f.importe_calc !== undefined) return f.importe_calc;
    return (f.lineas ?? []).reduce((s, l) => s + (Number(l.total) || 0), 0);
  }

  abonado(f: Factura): number {
    if (f.abonado_calc !== undefined) return f.abonado_calc;
    return (f.abonos ?? []).reduce((s, a) => s + (a.lineas ?? []).reduce((ss, l) => ss + (Number(l.total) || 0), 0), 0);
  }

  remaining(f: Factura): number {
    return Math.round((this.importe(f) - this.abonado(f)) * 100) / 100;
  }

  // ── Row interaction ────────────────────────────────────────────────────────
  onRowClick(numFact: number) {
    this.selectedRow.set(numFact);
  }

  onRowDblClick(f: Factura) {
    if (f.estado === 'abono') return;
    const same = this.expandedRow() === f.num_fact;
    this.expandedRow.set(same ? null : f.num_fact);
    if (!same) {
      this.actionStep.set('idle');
      this.abonoInput.set('');
      this.actionError.set('');
    }
    this.selectedRow.set(f.num_fact);
  }

  // ── Acciones: Borrador ────────────────────────────────────────────────────
  startDeleteConfirm() { this.actionStep.set('delete-confirm'); }

  doDeleteFactura(f: Factura) {
    this.svc.deleteFactura(f.num_fact).subscribe({
      next: () => {
        this.expandedRow.set(null);
        this.selectedRow.set(null);
        const page = this.facturas().length === 1 && this.currentPage() > 1
          ? this.currentPage() - 1
          : this.currentPage();
        this.load(page);
      },
      error: (e) => this.actionError.set(e.error?.message ?? 'Error al eliminar la factura'),
    });
  }

  // ── Acciones: Emitida — Abono parcial ─────────────────────────────────────
  startAbonoInput() {
    this.abonoInput.set('');
    this.actionError.set('');
    this.actionStep.set('abono-input');
  }

  abonoInputValid(f: Factura): boolean {
    const v = parseFloat(this.abonoInput());
    return isFinite(v) && v > 0 && v <= this.remaining(f);
  }

  goAbonoConfirm(f: Factura) {
    if (!this.abonoInputValid(f)) {
      this.actionError.set(`Introduce un importe entre 0.01 € y ${this.remaining(f).toFixed(2)} €`);
      return;
    }
    this.actionError.set('');
    this.actionStep.set('abono-confirm');
  }

  doAbonoParcial(f: Factura) {
    const importe = parseFloat(this.abonoInput());
    this.svc.crearAbono(f.num_fact, importe).subscribe({
      next: () => { this.expandedRow.set(null); this.load(this.currentPage()); },
      error: (e) => this.actionError.set(e.error?.message ?? 'Error al crear el abono'),
    });
  }

  // ── Acciones: Emitida — Anular completa ───────────────────────────────────
  startAnularConfirm() {
    this.actionError.set('');
    this.actionStep.set('anular-confirm');
  }

  doAnularCompleta(f: Factura) {
    this.svc.crearAbono(f.num_fact).subscribe({
      next: () => { this.expandedRow.set(null); this.load(this.currentPage()); },
      error: (e) => this.actionError.set(e.error?.message ?? 'Error al anular la factura'),
    });
  }

  // ── Cancelar cualquier acción ─────────────────────────────────────────────
  cancelAction() {
    this.actionStep.set('idle');
    this.abonoInput.set('');
    this.actionError.set('');
  }

  // ── Helpers ───────────────────────────────────────────────────────────────
  estadoClass(est: string): string {
    return `fact-badge--${est.replace(' ', '-')}`;
  }

  isNumber(x: number | '...'): x is number { return typeof x === 'number'; }
}
