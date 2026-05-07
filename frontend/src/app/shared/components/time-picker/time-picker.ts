import {
  Component, ElementRef, forwardRef, HostListener,
  Input, signal, ViewChild,
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR, ReactiveFormsModule } from '@angular/forms';

@Component({
  selector: 'app-time-picker',
  imports: [ReactiveFormsModule],
  templateUrl: './time-picker.html',
  styleUrl: './time-picker.scss',
  providers: [{
    provide: NG_VALUE_ACCESSOR,
    useExisting: forwardRef(() => TimePickerComponent),
    multi: true,
  }],
})
export class TimePickerComponent implements ControlValueAccessor {
  @Input() invalid = false;
  @Input() placeholder = 'HH:MM';
  @ViewChild('hourList')   hourListRef!:   ElementRef<HTMLUListElement>;
  @ViewChild('minuteList') minuteListRef!: ElementRef<HTMLUListElement>;

  displayValue = signal('');
  open         = signal(false);
  selectedH    = signal<number | null>(null);
  selectedM    = signal<number | null>(null);
  disabled     = signal(false);

  hours   = Array.from({ length: 24 }, (_, i) => i);
  minutes = Array.from({ length: 60 }, (_, i) => i);

  private onChange: (v: string) => void = () => {};
  private onTouched: () => void         = () => {};

  // ── ControlValueAccessor ──────────────────────────────────────────────────
  writeValue(value: string): void {
    if (value && /^\d{2}:\d{2}$/.test(value)) {
      const [h, m] = value.split(':').map(Number);
      this.selectedH.set(h);
      this.selectedM.set(m);
      this.displayValue.set(value);
    } else {
      this.selectedH.set(null);
      this.selectedM.set(null);
      this.displayValue.set(value ?? '');
    }
  }

  registerOnChange(fn: (v: string) => void): void { this.onChange = fn; }
  registerOnTouched(fn: () => void): void          { this.onTouched = fn; }
  setDisabledState(isDisabled: boolean): void       { this.disabled.set(isDisabled); }

  // ── Text input ────────────────────────────────────────────────────────────
  onInputChange(event: Event): void {
    const val = (event.target as HTMLInputElement).value;
    this.displayValue.set(val);
    this.onChange(val);
    if (/^\d{2}:\d{2}$/.test(val)) {
      const [h, m] = val.split(':').map(Number);
      if (h >= 0 && h <= 23 && m >= 0 && m <= 59) {
        this.selectedH.set(h);
        this.selectedM.set(m);
      }
    }
  }

  // ── Dropdown ──────────────────────────────────────────────────────────────
  togglePicker(): void {
    if (this.disabled()) return;
    if (this.open()) { this.close(); return; }
    this.open.set(true);
    setTimeout(() => this.scrollToSelected(), 0);
  }

  pickHour(h: number): void {
    this.selectedH.set(h);
    this.updateFromPicker();
  }

  pickMinute(m: number): void {
    this.selectedM.set(m);
    this.updateFromPicker();
    this.close();
  }

  private updateFromPicker(): void {
    const h = this.selectedH();
    const m = this.selectedM();
    if (h !== null && m !== null) {
      const val = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
      this.displayValue.set(val);
      this.onChange(val);
    }
  }

  private close(): void {
    this.open.set(false);
    this.onTouched();
  }

  private scrollToSelected(): void {
    const scrollTo = (el: HTMLUListElement, idx: number) => {
      const item = el.children[idx] as HTMLElement | undefined;
      if (item) el.scrollTop = item.offsetTop - el.clientHeight / 2 + item.clientHeight / 2;
    };
    const h = this.selectedH();
    const m = this.selectedM();
    if (this.hourListRef)   scrollTo(this.hourListRef.nativeElement,   h ?? 0);
    if (this.minuteListRef) scrollTo(this.minuteListRef.nativeElement, m ?? 0);
  }

  pad(n: number): string { return String(n).padStart(2, '0'); }

  // ── Close on outside click ────────────────────────────────────────────────
  @HostListener('document:click', ['$event'])
  onDocumentClick(e: MouseEvent): void {
    if (this.open() && !(e.target as Element).closest('app-time-picker')) {
      this.close();
    }
  }
}
