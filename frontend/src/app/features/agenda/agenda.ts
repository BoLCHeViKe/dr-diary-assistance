import { Component, HostListener, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { CurrencyPipe } from '@angular/common';
import { AgendaService, Agenda, Cita, Especialidad, Prestacion } from '../../core/services/agenda.service';
import { Paciente } from '../../core/services/paciente.service';
import { AuthService } from '../../core/services/auth.service';
import { PacienteSelectorComponent } from '../../shared/components/paciente-selector/paciente-selector';
import { AgendaCalendarioComponent } from './agenda-calendario/agenda-calendario';

type PanelMode = null | 'create-agenda' | 'create-cita' | 'view-cita';
type EstadoCita = 'citado' | 'en espera' | 'atendido' | 'facturado';

@Component({
  selector: 'app-agenda',
  imports: [ReactiveFormsModule, CurrencyPipe, PacienteSelectorComponent, AgendaCalendarioComponent],
  templateUrl: './agenda.html',
  styleUrl: './agenda.scss',
})
export class AgendaComponent implements OnInit {
  private readonly svc  = inject(AgendaService);
  private readonly auth = inject(AuthService);
  private readonly fb   = inject(FormBuilder);

  // ── State ─────────────────────────────────────────────────────────────────
  selectedDate = signal(new Date());
  agenda       = signal<Agenda | null>(null);
  citas        = signal<Cita[]>([]);
  loading      = signal(false);
  error        = signal('');
  panelError   = signal(''); // errors shown inside the panel, not at page level

  // ── Panel ─────────────────────────────────────────────────────────────────
  panelMode       = signal<PanelMode>(null);
  selectedCita    = signal<Cita | null>(null);
  selectedSlot    = signal('');
  selectedPatient = signal<Paciente | null>(null); // patient picked for a new cita

  // ── Catalogues ────────────────────────────────────────────────────────────
  especialidades = signal<Especialidad[]>([]);
  prestaciones   = signal<Prestacion[]>([]);
  selectedEsp    = signal('');

  // ── Calendar dropdown ─────────────────────────────────────────────────────
  calendarOpen   = signal(false);
  todasAgendas   = signal<Agenda[]>([]);
  diasConAgenda  = computed(() => new Set(this.todasAgendas().map(a => a.fecha)));

  // ── Saving flags ──────────────────────────────────────────────────────────
  savingAgenda   = signal(false);
  savingCita     = signal(false);
  updatingEstado = signal(false);

  // ── Billing sub-panel ─────────────────────────────────────────────────────
  billingOpen    = signal(false);
  billingCantidad = signal(1);
  billingPrecio  = signal(0);
  billingError   = signal('');
  savingBilling  = signal(false);

  // ── Forms ─────────────────────────────────────────────────────────────────
  agendaForm = this.fb.nonNullable.group({
    h_inicio:      ['09:00', Validators.required],
    h_fin:         ['14:00', Validators.required],
    min_intervalo: [15,      Validators.required],
  });

  citaForm = this.fb.nonNullable.group({
    codigo_esp: ['', Validators.required],
    id_prest:   [0,  [Validators.required, Validators.min(1)]],
  });

  // ── Computed ──────────────────────────────────────────────────────────────
  dateStr = computed(() => {
    const d = this.selectedDate();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  });

  dateLong = computed(() =>
    this.selectedDate().toLocaleDateString('es-ES', {
      weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    })
  );

  dateDMY = computed(() => {
    const d = this.selectedDate();
    return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
  });

  slots = computed(() => {
    const ag = this.agenda();
    if (!ag) return [];
    const result: string[] = [];
    let cur = this.timeToMins(ag.h_inicio);
    const end = this.timeToMins(ag.h_fin);
    while (cur < end) {
      result.push(this.minsToTime(cur));
      cur += ag.min_intervalo;
    }
    return result;
  });

  slotMap = computed(() => {
    const map = new Map<string, Cita>();
    for (const c of this.citas()) {
      map.set(c.h_cita.substring(0, 5), c);
    }
    return map;
  });

  freeSlotsCount = computed(() =>
    this.slots().filter(s => !this.slotMap().has(s)).length
  );

  filteredPrestaciones = computed(() =>
    this.prestaciones().filter(p => p.codigo_esp === this.selectedEsp())
  );

  get medicoId(): number {
    return this.auth.getUser()?.id ?? 0;
  }

  readonly INTERVALOS: number[] = [5, 10, 15, 20, 30, 45, 60];
  readonly ESTADOS: EstadoCita[] = ['citado', 'en espera', 'atendido', 'facturado'];

  // ── Lifecycle ─────────────────────────────────────────────────────────────
  ngOnInit() {
    this.loadAgenda();
    this.loadCatalogues();
  }

  // ── Navigation ────────────────────────────────────────────────────────────
  prevDay() {
    const d = new Date(this.selectedDate());
    d.setDate(d.getDate() - 1);
    this.selectedDate.set(d);
    this.loadAgenda();
  }

  nextDay() {
    const d = new Date(this.selectedDate());
    d.setDate(d.getDate() + 1);
    this.selectedDate.set(d);
    this.loadAgenda();
  }

  goToday() {
    this.selectedDate.set(new Date());
    this.loadAgenda();
  }

  onDateChange(event: Event) {
    const val = (event.target as HTMLInputElement).value;
    if (val) {
      this.selectedDate.set(new Date(val + 'T12:00:00'));
      this.loadAgenda();
    }
  }

  // ── Load ──────────────────────────────────────────────────────────────────
  private loadAgenda() {
    if (!this.medicoId) return;
    this.loading.set(true);
    this.error.set('');
    this.agenda.set(null);
    this.citas.set([]);
    this.panelMode.set(null);
    this.calendarOpen.set(false);

    this.svc.getAgendasMedico(this.medicoId).subscribe({
      next: (agendas) => {
        this.todasAgendas.set(agendas);
        const found = agendas.find(a => a.fecha === this.dateStr());
        if (found) {
          this.agenda.set(found);
          this.svc.getCitasAgenda(found.id_agenda).subscribe({
            next: (res) => { this.citas.set(res.citas); this.loading.set(false); },
            error: ()    => { this.loading.set(false); },
          });
        } else {
          this.loading.set(false);
        }
      },
      error: (e) => {
        this.error.set(
          e.status === 404
            ? 'Este usuario no tiene perfil de médico asignado.'
            : 'Error al cargar la agenda.'
        );
        this.loading.set(false);
      },
    });
  }

  private loadCatalogues() {
    this.svc.getEspecialidades().subscribe(e => this.especialidades.set(e));
    this.svc.getPrestaciones().subscribe(p => this.prestaciones.set(p));
  }

  // ── Panel ─────────────────────────────────────────────────────────────────
  openPanel(mode: PanelMode) { this.panelMode.set(mode); }

  closePanel() {
    this.panelMode.set(null);
    this.selectedCita.set(null);
    this.selectedSlot.set('');
    this.selectedPatient.set(null);
    this.selectedEsp.set('');
    this.panelError.set('');
    this.billingOpen.set(false);
    this.billingError.set('');
    this.agendaForm.reset({ h_inicio: '09:00', h_fin: '14:00', min_intervalo: 15 });
    this.citaForm.reset({ codigo_esp: '', id_prest: 0 });
  }

  openNewCita(slot: string) {
    this.selectedSlot.set(slot);
    this.selectedPatient.set(null);
    this.selectedEsp.set('');
    this.panelError.set('');
    this.citaForm.reset({ codigo_esp: '', id_prest: 0 });
    this.panelMode.set('create-cita');
  }

  openCita(cita: Cita) {
    this.selectedCita.set(cita);
    this.panelMode.set('view-cita');
  }

  // ── Patient selection (from inline PacienteSelector) ─────────────────────
  onPacienteSeleccionado(p: Paciente) {
    this.selectedPatient.set(p);
    this.panelError.set('');
  }

  clearPatient() {
    this.selectedPatient.set(null);
  }

  onEspChange(event: Event) {
    this.selectedEsp.set((event.target as HTMLSelectElement).value);
    this.citaForm.patchValue({ id_prest: 0 });
  }

  // ── Actions ───────────────────────────────────────────────────────────────
  submitCreateAgenda() {
    if (this.agendaForm.invalid) { this.agendaForm.markAllAsTouched(); return; }
    this.savingAgenda.set(true);
    const { h_inicio, h_fin, min_intervalo } = this.agendaForm.getRawValue();
    this.svc.createAgenda(this.medicoId, { fecha: this.dateStr(), h_inicio, h_fin, min_intervalo }).subscribe({
      next: () => { this.savingAgenda.set(false); this.closePanel(); this.loadAgenda(); },
      error: (e) => {
        this.error.set(e.error?.error ?? 'Error al crear la agenda.');
        this.savingAgenda.set(false);
      },
    });
  }

  submitCreateCita() {
    if (!this.selectedPatient()) { this.panelError.set('Selecciona un paciente antes de continuar.'); return; }
    if (this.citaForm.invalid)   { this.citaForm.markAllAsTouched(); return; }
    const ag = this.agenda();
    if (!ag) return;
    this.savingCita.set(true);
    const { codigo_esp, id_prest } = this.citaForm.getRawValue();
    this.svc.createCita(ag.id_agenda, {
      id_paciente: this.selectedPatient()!.id_paciente,
      codigo_esp,
      id_prest,
      h_cita: this.selectedSlot(),
    }).subscribe({
      next: () => { this.savingCita.set(false); this.closePanel(); this.loadAgenda(); },
      error: (e) => {
        this.panelError.set(e.error?.error ?? 'Error al crear la cita.');
        this.savingCita.set(false);
      },
    });
  }

  setEstado(estado: EstadoCita) {
    const cita = this.selectedCita();
    const ag   = this.agenda();
    if (!cita || !ag) return;
    this.updatingEstado.set(true);
    this.svc.updateCitaEstado(ag.id_agenda, cita.id_cita, estado).subscribe({
      next: (updated) => {
        this.selectedCita.set(updated);
        this.updatingEstado.set(false);
        this.loadCitas();
      },
      error: () => { this.updatingEstado.set(false); },
    });
  }

  cancelCita() {
    const cita = this.selectedCita();
    const ag   = this.agenda();
    if (!cita || !ag || !confirm('¿Cancelar esta cita?')) return;
    this.svc.deleteCita(ag.id_agenda, cita.id_cita).subscribe({
      next: () => { this.closePanel(); this.loadAgenda(); },
      error: (e) => { this.error.set(e.error?.message ?? 'Error al cancelar la cita.'); },
    });
  }

  confirmDeleteAgenda() {
    const ag = this.agenda();
    if (!ag || !confirm('¿Eliminar la agenda de este día? Solo es posible si no tiene citas.')) return;
    this.svc.deleteAgenda(this.medicoId, ag.id_agenda).subscribe({
      next: () => { this.loadAgenda(); },
      error: (e) => { this.error.set(e.error?.message ?? 'No se puede eliminar la agenda.'); },
    });
  }

  // ── Calendar toggle ───────────────────────────────────────────────────────
  toggleCalendar(event: Event) {
    event.stopPropagation();
    this.calendarOpen.update(v => !v);
  }

  onCalendarDateSelected(date: Date) {
    this.selectedDate.set(date);
    this.calendarOpen.set(false);
    this.loadAgenda();
  }

  @HostListener('document:click')
  onDocClick() { this.calendarOpen.set(false); }

  // ── Billing sub-panel ─────────────────────────────────────────────────────
  openBilling() {
    const cita = this.selectedCita();
    if (!cita) return;
    this.billingCantidad.set(1);
    this.billingPrecio.set(cita.prestacion?.precio ?? 0);
    this.billingError.set('');
    this.billingOpen.set(true);
  }

  closeBilling() {
    this.billingOpen.set(false);
    this.billingError.set('');
  }

  confirmBilling() {
    const cita = this.selectedCita();
    const ag   = this.agenda();
    if (!cita || !ag) return;
    const precio   = this.billingPrecio();
    const cantidad = this.billingCantidad();
    const total    = cantidad * precio;
    const nombre   = `${cita.paciente?.apellido1 ?? ''} ${cita.paciente?.nombre ?? ''}`.trim();
    if (!confirm(`¿Desea facturar al paciente ${nombre} por un total de ${total.toFixed(2)} €?`)) return;
    this.savingBilling.set(true);
    this.svc.facturarCita(ag.id_agenda, cita.id_cita, { cantidad, precio }).subscribe({
      next: (updated) => {
        this.selectedCita.set(updated);
        this.savingBilling.set(false);
        this.closeBilling();
        this.loadCitas();
      },
      error: (e) => {
        this.billingError.set(e.error?.error ?? 'Error al facturar');
        this.savingBilling.set(false);
      },
    });
  }

  onEstadoClick(est: EstadoCita) {
    if (est === 'facturado') {
      this.openBilling();
    } else {
      this.setEstado(est);
    }
  }

  // ── Helpers ───────────────────────────────────────────────────────────────
  estadoClass(estado: string): string {
    return 'estado-' + estado.replace(' ', '-');
  }

  fmt(t: string): string {
    return t.substring(0, 5);
  }

  private loadCitas() {
    const ag = this.agenda();
    if (!ag) return;
    this.svc.getCitasAgenda(ag.id_agenda).subscribe({
      next: (res) => this.citas.set(res.citas),
    });
  }

  private timeToMins(t: string): number {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
  }

  private minsToTime(mins: number): string {
    return `${Math.floor(mins / 60).toString().padStart(2, '0')}:${(mins % 60).toString().padStart(2, '0')}`;
  }
}
