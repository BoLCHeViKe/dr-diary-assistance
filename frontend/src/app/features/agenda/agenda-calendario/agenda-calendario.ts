import { Component, computed, input, output, signal } from '@angular/core';

type CalDay = { date: Date; iso: string } | null;

@Component({
  selector: 'app-agenda-calendario',
  templateUrl: './agenda-calendario.html',
  styleUrl: './agenda-calendario.scss',
})
export class AgendaCalendarioComponent {
  // Inputs
  selectedDate  = input.required<Date>();
  diasConAgenda = input<Set<string>>(new Set());

  // Output
  dateSelected = output<Date>();

  // Month offset from selected date's month (signal so computed tracks it)
  private offset = signal(0);

  displayDate = computed(() => {
    const base = this.selectedDate();
    return new Date(base.getFullYear(), base.getMonth() + this.offset(), 1);
  });

  monthLabel = computed(() =>
    this.displayDate().toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
  );

  calendarWeeks = computed((): CalDay[][] => {
    const d    = this.displayDate();
    const year = d.getFullYear();
    const mon  = d.getMonth();
    const firstDow  = (new Date(year, mon, 1).getDay() + 6) % 7; // Mon=0
    const daysInMon = new Date(year, mon + 1, 0).getDate();

    const weeks: CalDay[][] = [];
    let week: CalDay[] = Array(firstDow).fill(null);

    for (let day = 1; day <= daysInMon; day++) {
      week.push({ date: new Date(year, mon, day), iso: this.toISO(year, mon, day) });
      if (week.length === 7) { weeks.push(week); week = []; }
    }
    if (week.length > 0) {
      while (week.length < 7) week.push(null);
      weeks.push(week);
    }
    return weeks;
  });

  readonly DOW = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

  prevMonth() { this.offset.update(v => v - 1); }
  nextMonth() { this.offset.update(v => v + 1); }

  selectDay(day: CalDay) {
    if (!day) return;
    this.offset.set(0);
    this.dateSelected.emit(day.date);
  }

  isSelected(day: CalDay): boolean {
    if (!day) return false;
    return day.iso === this.toISO(
      this.selectedDate().getFullYear(),
      this.selectedDate().getMonth(),
      this.selectedDate().getDate()
    );
  }

  hasAgenda(day: CalDay): boolean {
    return !!day && this.diasConAgenda().has(day.iso);
  }

  isToday(day: CalDay): boolean {
    if (!day) return false;
    const t = new Date();
    return day.iso === this.toISO(t.getFullYear(), t.getMonth(), t.getDate());
  }

  private toISO(y: number, m: number, d: number): string {
    return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
  }
}
