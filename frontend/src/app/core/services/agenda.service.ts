import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface Agenda {
  id_agenda: number;
  fecha: string;
  h_inicio: string;
  h_fin: string;
  min_intervalo: number;
  id_med: number;
}

export interface Cita {
  id_cita: number;
  id_agenda: number;
  h_cita: string;
  estado: 'citado' | 'en espera' | 'validado' | 'facturado';
  codigo_esp: string;
  id_prest: number;
  id_paciente: number;
  paciente?: Paciente;
  prestacion?: Prestacion;
}

export interface Paciente {
  id_paciente: number;
  nombre: string;
  apellido1: string;
  apellido2?: string;
  dni: string;
  telf?: string;
}

export interface Especialidad {
  codigo_esp: string;
  nombre: string;
}

export interface Prestacion {
  codigo_esp: string;
  id_prest: number;
  nombre: string;
  descripcion?: string;
  precio: number;
}

@Injectable({ providedIn: 'root' })
export class AgendaService {
  private readonly http = inject(HttpClient);

  getAgendasMedico(idMedico: number) {
    return this.http.get<Agenda[]>(`/api/medicos/${idMedico}/agendas`);
  }

  createAgenda(idMedico: number, data: Omit<Agenda, 'id_agenda' | 'id_med'>) {
    return this.http.post<Agenda>(`/api/medicos/${idMedico}/agendas`, data);
  }

  deleteAgenda(idMedico: number, idAgenda: number) {
    return this.http.delete(`/api/medicos/${idMedico}/agendas/${idAgenda}`);
  }

  getCitasAgenda(idAgenda: number) {
    return this.http.get<{ agenda: Agenda; citas: Cita[] }>(`/api/agendas/${idAgenda}/citas`);
  }

  createCita(idAgenda: number, data: { id_paciente: number; codigo_esp: string; id_prest: number; h_cita: string }) {
    return this.http.post<Cita>(`/api/agendas/${idAgenda}/citas`, data);
  }

  updateCitaEstado(idAgenda: number, idCita: number, estado: string) {
    return this.http.put<Cita>(`/api/agendas/${idAgenda}/citas/${idCita}`, { estado });
  }

  deleteCita(idAgenda: number, idCita: number) {
    return this.http.delete(`/api/agendas/${idAgenda}/citas/${idCita}`);
  }

  getPacientes() {
    return this.http.get<Paciente[]>('/api/pacientes');
  }

  getEspecialidades() {
    return this.http.get<Especialidad[]>('/api/especialidades');
  }

  getPrestaciones() {
    return this.http.get<Prestacion[]>('/api/prestaciones');
  }
}
