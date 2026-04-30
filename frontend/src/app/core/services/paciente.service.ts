import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface Paciente {
  id_paciente: number;
  nombre: string;
  apellido1: string;
  apellido2?: string;
  fecha_nac?: string;
  dni: string;
  telf?: string;
  email?: string;
  direccion?: string;
  nhc?: number;
  hc?: { nhc: number; fecha_apert: string };
}

export interface PacienteForm {
  nombre: string;
  apellido1: string;
  apellido2?: string;
  fecha_nac?: string;
  dni: string;
  telf?: string;
  email?: string;
  direccion?: string;
}

@Injectable({ providedIn: 'root' })
export class PacienteService {
  private readonly http = inject(HttpClient);

  getAll()                                        { return this.http.get<Paciente[]>('/api/pacientes'); }
  getById(id: number)                             { return this.http.get<Paciente>(`/api/pacientes/${id}`); }
  create(data: PacienteForm)                      { return this.http.post<Paciente>('/api/pacientes', data); }
  update(id: number, data: Partial<PacienteForm>) { return this.http.put<Paciente>(`/api/pacientes/${id}`, data); }
  delete(id: number)                              { return this.http.delete(`/api/pacientes/${id}`); }
}
