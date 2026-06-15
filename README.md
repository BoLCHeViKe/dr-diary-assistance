# 🩺 Dr. Diary Assistance

> **Plataforma web SPA de gestión integral para consultas médicas privadas**  
> Digitalización completa del flujo clínico-administrativo: desde la cita hasta la factura.

---

## 📌 Descripción del Proyecto

**Dr. Diary Assistance** es una aplicación web **Single Page Application (SPA)** desarrollada como proyecto final de DAW (nota media 9,88 · Matrícula de Honor), orientada a digitalizar y automatizar la gestión administrativa de consultas médicas privadas.

El sistema cubre el ciclo completo de la consulta:

```
Paciente agenda cita → Triaje y atención → Historia Clínica → Facturación → Gestión de abonos
```

Diseñada para ser **ligera, intuitiva y escalable**, se presenta como alternativa a soluciones sobredimensionadas del mercado (OMI360, Diraya), enfocándose en el médico autónomo o pequeña clínica.

---

## 🚀 Demo & Despliegue

> El proyecto está desplegado en servidor propio (NAS QNAP TS-464) con dominio real, accesible vía HTTPS con certificado SSL de Let's Encrypt.

```
https://dr.diary.[dominio].es
```

**Credenciales de prueba:**
| Rol | Email | Contraseña |
|-----|-------|-----------|
| Super Admin | admin@demo.com | (contactar) |
| Médico | medico@demo.com | (contactar) |

---

## ✨ Funcionalidades Principales

### 🗓️ Gestión de Agenda
- Calendario interactivo con visualización de citas del día
- Creación de citas en slots disponibles con asignación de especialidad y acto
- Estados de cita: **Citado → En Espera → Atendido → Facturado**
- Responsive: adaptado a desktop, tablet y móvil

### 👤 Gestión de Pacientes
- Alta automática de Historia Clínica al crear paciente (trigger en BBDD)
- Búsqueda por DNI o Número de Historia Clínica (NHC)
- CRUD completo con validaciones

### 📋 Historia Clínica
- Registro de episodios clínicos (síntomas, diagnóstico, tratamiento)
- Acceso restringido exclusivamente al rol médico
- Consulta histórica ordenada cronológicamente

### 🧾 Facturación
- Facturación directa desde la agenda o desde el módulo de facturación
- Soporte de múltiples líneas y unidades por factura
- Sistema de **abonos parciales y anulación total** con coherencia financiera (el total nunca puede superar el importe original — validado por trigger)
- Listado de facturas filtrable por fechas, estado, especialidad y paciente

### 🔐 Gestión de Roles y Permisos
- Sistema de roles granular: Super Admin, Admin, Médico (ampliable a Enfermero, Recepción, etc.)
- Guards en Angular para protección de rutas por rol/permiso
- Middlewares en Laravel para seguridad en capa API
- Autenticación con tokens Bearer (Laravel Sanctum)

### ⚙️ Panel de Administración
- Gestión de usuarios (crear, editar, deshabilitar)
- Gestión de especialidades y prestaciones/actos con precios
- Asignación de roles y permisos personalizados

---

## 🛠️ Stack Tecnológico

### Frontend
| Tecnología | Versión | Uso |
|-----------|---------|-----|
| **Angular** | 21 | Framework SPA principal |
| **TypeScript** | 5.9 | Lenguaje principal |
| **RxJS** | 7 | Streams y llamadas HTTP |
| **Angular Signals** | — | Gestión de estado reactivo |
| **Angular Guards** | — | Protección de rutas por rol |
| **Bootstrap** | 5.3 | Grid, componentes y utilidades |
| **Bootstrap Icons** | 1.13 | Iconografía médica |
| **SCSS / Sass** | — | Estilos y variables CSS |
| **Angular CLI + Vite** | 8 | Build y servidor de desarrollo |

### Backend
| Tecnología | Versión | Uso |
|-----------|---------|-----|
| **Laravel** | 13 | Framework API REST (MVC) |
| **PHP** | 8.4 | Lenguaje backend |
| **Eloquent ORM** | — | Abstracción y comunicación con BBDD |
| **Laravel Sanctum** | — | Autenticación API con tokens Bearer |
| **Bcrypt** | — | Hash seguro de contraseñas |
| **Composer + Artisan** | — | Gestión de dependencias, migraciones y seeders |

### Base de Datos
| Tecnología | Versión | Detalle |
|-----------|---------|---------|
| **MariaDB** | 11 | Motor principal |
| **InnoDB** | — | Storage engine con soporte ACID |
| **MySQL Workbench** | — | Diseño y administración |

> Modelo relacional normalizado (3FN) con claves primarias autoincrementales, índices FK optimizados, vistas, triggers y procedimientos almacenados.

### Infraestructura & DevOps
| Tecnología | Uso |
|-----------|-----|
| **Docker + Docker Compose** | Contenedores: Frontend, Backend, MariaDB |
| **Nginx** | Servidor web estáticos + Reverse Proxy |
| **Nginx Proxy Manager** | Gestión SSL y enrutamiento |
| **Let's Encrypt** | Certificado SSL auto-renovable |
| **Supervisor** | Gestión de procesos PHP-FPM en producción |
| **DDNS Script** | Actualización automática de IP dinámica |
| **VLAN / DMZ** | Aislamiento de red: zona pública vs. LAN |
| **AES-256** | Cifrado de backups |
| **Snapshot inmutables** | Plan de contingencia contra ransomware (cada 12h) |
| **VMware** | Entorno de desarrollo virtualizado (Ubuntu Server sin GUI) |
| **VS Code + SSH** | IDE remoto sobre la máquina virtual |
| **GitHub** | Control de versiones y backup en la nube |
| **Bruno** | Testing de API REST |

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                        INTERNET                             │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTPS :443
                    ┌──────▼───────┐
                    │   Router +   │
                    │  Firewall    │  ← VLAN50 DMZ (sucia)
                    │  DDNS Script │
                    └──────┬───────┘
                           │ :10443
              ┌────────────▼────────────┐
              │   Docker: NGINX         │  ← Reverse Proxy
              │   (red macvlan)         │    SSL Let's Encrypt
              └────────────┬────────────┘
                    ┌──────┴──────┐
          ┌─────────▼──┐    ┌────▼──────────┐
          │ Docker:    │    │ Docker:       │
          │ Angular    │    │ Laravel +     │
          │ (Nginx)    │    │ PHP-FPM       │
          └────────────┘    └──────┬────────┘
                                   │
                          ┌────────▼────────┐
                          │ Docker:         │
                          │ MariaDB 11      │
                          │ (Bindmount)     │
                          └─────────────────┘
```

**Arquitectura lógica:** MVC desacoplado — Angular (SPA) ↔ API REST JSON ↔ Laravel ↔ MariaDB

---

## 📁 Estructura del Repositorio

```
dr-diary-assistance/
├── backend/                    # Laravel 13 (PHP 8.4)
│   ├── app/Http/Controllers/Api/   # Controladores REST
│   ├── app/Models/             # Modelos Eloquent
│   ├── database/
│   │   ├── migrations/         # Creación de tablas y triggers
│   │   └── seeders/            # Datos iniciales (mock)
│   ├── routes/api.php          # Rutas API REST
│   ├── Dockerfile              # Build desarrollo
│   └── Dockerfile.prod         # Build producción
├── frontend/                   # Angular 21 (TypeScript 5.9)
│   ├── src/app/
│   │   ├── components/         # Componentes (TS + HTML + SCSS)
│   │   ├── services/           # Servicios HTTP
│   │   ├── interfaces/         # Modelos de datos TS
│   │   └── guards/             # Guards de roles y autenticación
│   └── Dockerfile
├── docker-compose.yml          # Orquestación producción
├── docker-compose.lan.yml      # Orquestación desarrollo local
├── .env.example_lan            # Variables entorno desarrollo
├── .env.example_online         # Variables entorno producción
└── README.md
```

---

## ⚡ Instalación y Despliegue

### Requisitos previos
- Docker & Docker Compose instalados
- Puerto 443 disponible (o configurable)

### Despliegue en 4 pasos

```bash
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/dr-diary-assistance.git
cd dr-diary-assistance

# 2. Configurar variables de entorno
cp .env.example_lan .env   # Para desarrollo local
# cp .env.example_online .env  # Para producción
# Editar .env con tus credenciales de BBDD y configuración

# 3. Levantar contenedores
docker compose up -d --build

# 4. Generar clave de aplicación (solo primera vez)
docker compose exec backend php artisan key:generate --show
# Añadir la clave generada al .env y reiniciar:
docker compose down && docker compose up -d
```

> **Nota:** La primera vez se creará automáticamente la carpeta `mysql_data/` con la persistencia de la base de datos (bindmount). Las migraciones y seeders se ejecutan automáticamente.

### Backups de base de datos

```bash
# Dump manual de la BBDD
docker exec dda_mariadb mariadb-dump -u root -p"${DB_ROOT_PASSWORD}" \
  drdiaryassistance > backup_$(date +%Y%m%d_%H%M%S).sql
```

---

## 🗄️ Modelo de Datos (Resumen)

```
USUARIO ──< ADMINS
         └──< MEDICO ──< AGENDA ──< CITA ──> PACIENTE ──> HC ──< DETALLEHC
                                       └──> PRESTACION ──> ESPECIALIDAD
FACTURA ──< LINEAFACTURA ──> PRESTACION
FACTURA ──> PACIENTE
```

**Entidades principales:** USUARIO · MEDICO · ADMIN · PACIENTE · HC · DETALLEHC · AGENDA · CITA · ESPECIALIDAD · PRESTACION · FACTURA · LINEAFACTURA

---

## ✅ Pruebas

Pruebas funcionales realizadas sobre la API REST con **Bruno** (alternativa open source a Postman):

| Caso de Uso | Estado | Fecha |
|-------------|--------|-------|
| Login / Autenticación | ✅ PASS | 12/05/2026 |
| Incorporar episodio clínico | ✅ PASS | 12/05/2026 |
| Facturar acto desde agenda | ✅ PASS | 13/05/2026 |
| Gestionar Historia Clínica | ✅ PASS | 14/05/2026 |
| Elaborar episodio clínico | ✅ PASS | 14/05/2026 |
| Añadir especialidades y actos | ✅ PASS | 15/05/2026 |
| Agendar y citar paciente | ✅ PASS | 15/05/2026 |

---

## 🗺️ Roadmap (Trabajo Futuro)

- [ ] Integración de mutuas y entidades financiadoras
- [ ] Dashboard con KPIs y estadísticas (ticket medio, facturación comparativa)
- [ ] Exportación de facturas a PDF e informes a XLS
- [ ] Envío de emails con SMTP (recuperación de contraseña, informes cifrados)
- [ ] Gestión de turnos en pantalla
- [ ] Criterio de caja: Facturado vs. Cobrado vs. Pendiente
- [ ] Modelos de IVA/IRPF y exportación XML para la AEAT
- [ ] Despliegue en servidor cloud dedicado con Fail2Ban

---

## 📐 Metodología

Proyecto desarrollado siguiendo la metodología **MÉTRICA v3** (estándar del Ministerio de Hacienda de España, basada en ISO/IEC 12207) con marco de trabajo **Scrum** (PSM I certificado), cubriendo todas las fases: Viabilidad → Análisis → Diseño → Construcción → Implantación → Pruebas.

**Duración total:** 280 horas · Sep 2025 – May 2026

---

## 👨‍💻 Autor

**Julio Alberto Fernández Fuentes**  
Software Engineer · DAW Matrícula de Honor (9,88) · Licenciado en ADE · PSM I

[![LinkedIn](https://img.shields.io/badge/LinkedIn-blue?logo=linkedin)](https://es.linkedin.com/in/tu-perfil)
[![GitHub](https://img.shields.io/badge/GitHub-black?logo=github)](https://github.com/tu-usuario)

---

## 📄 Licencia

Este proyecto es de uso educativo y portfolio profesional. Contactar para uso comercial.
