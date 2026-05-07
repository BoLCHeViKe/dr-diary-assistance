import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface DetalleHc {
  num_orden:     number;
  nhc:           number;
  id_cita?:      number;
  mov_consulta?: string;
  tto:           string;
  f_consulta:    string;
  sinto?:        string;
  diag?:         string;
  cita?: {
    id_cita:    number;
    codigo_esp: string;
    estado:     string;
    agenda?: {
      id_agenda:       number;
      id_med:          number;
      medico_usuario?: {
        id:        number;
        nombre:    string;
        apellido1: string;
        apellido2?: string;
      };
    };
  };
}

export interface Hc {
  nhc:         number;
  fecha_apert: string;
  detalles?:   DetalleHc[];
}

export interface HcResponse {
  hc:       Hc;
  detalles: DetalleHc[];
}

export interface DetalleHcForm {
  mov_consulta?: string;
  tto:           string;
  sinto?:        string;
  diag?:         string;
  id_cita?:      number;
}

@Injectable({ providedIn: 'root' })
export class HcService {
  private readonly http = inject(HttpClient);

  getHcPorPaciente(idPaciente: number) {
    return this.http.get<HcResponse>(`/api/pacientes/${idPaciente}/hc`);
  }

  getDetalles(nhc: number) {
    return this.http.get<HcResponse>(`/api/hc/${nhc}/detalles`);
  }

  addDetalle(nhc: number, data: DetalleHcForm) {
    return this.http.post<DetalleHc>(`/api/hc/${nhc}/detalles`, data);
  }

  updateDetalle(nhc: number, numOrden: number, data: Partial<DetalleHcForm>) {
    return this.http.put<DetalleHc>(`/api/hc/${nhc}/detalles/${numOrden}`, data);
  }
}
