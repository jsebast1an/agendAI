# AgendAI — Contexto para Claude Code

## Qué es este proyecto

Recepcionista digital 24/7 para consultorios médicos en Ecuador.
Atiende pacientes por WhatsApp: responde dudas, consulta disponibilidad y agenda citas.
Multi-tenant — un backend sirve a múltiples consultorios.

## Stack

- **Backend:** Laravel 12 (PHP 8.2+) + MySQL
- **Frontend:** Inertia.js + React 18 + Tailwind CSS (landing page + futuro admin dashboard)
- **LLM:** Claude API (Anthropic) con tool calling nativo
- **Canal:** WhatsApp Business API (Meta Graph API v22.0)
- **Queue:** Laravel Queue (database driver) para debounce de mensajes
- **Cache:** Database driver (cross-process, necesario para debounce)
- **Testing:** PHPUnit, Laravel Pint (linter)
- **Dev server:** `composer dev` (levanta server + queue + pail + vite)

## Estructura clave

```
app/
├── Http/Controllers/
│   └── WhatsappWebhookController.php   # Webhook: recibe mensaje, acumula en cache, despacha job con delay
├── Jobs/
│   └── ProcessConversationJob.php      # Debounce: lock + pull mensajes pendientes, llama AnthropicService
├── Models/
│   ├── Appointment.php                 # Cita: patient, professional, service, start/end UTC, status, deposit_paid
│   ├── Conversation.php                # phone_number + org_id → conversación activa, context JSON, handoff
│   ├── ConversationMessage.php         # Mensajes (role: user|assistant)
│   ├── Organization.php                # Tenant: nombre, wa_phone_number, cancellation_hours_min
│   ├── Patient.php                     # Paciente: wa_id, nombre, telefono, org_id
│   ├── Professional.php               # Profesional: nombre, especialidad, org_id
│   ├── Schedule.php                    # Horario semanal: professional_id, day_of_week, start/end
│   ├── Service.php                     # Servicio: nombre, descripcion, org_id
│   └── ToolCallLog.php                # Log de tool calls para observabilidad
├── Services/
│   ├── AgendaToolsService.php          # Ejecuta tools: read-only + transaccionales
│   ├── AnthropicService.php            # Claude API: system prompt, tool definitions, tool loop, handoff
│   ├── AppointmentService.php          # Logica transaccional: confirm, cancel, reschedule
│   ├── OpenAIService.php               # Legacy (sin uso, pendiente de eliminar)
│   ├── PatientResolverService.php      # Resuelve/crea paciente por wa_id
│   ├── TenantResolverService.php       # Resuelve org por numero WhatsApp Business
│   └── WhatsappService.php             # Envia mensajes via Meta Graph API
config/services.php                     # Config de anthropic, waba (tokens, phone_id, etc.)
routes/api.php                          # POST|GET /webhook/whatsapp
database/seeders/DentalClinicSeeder.php # Seed de clinica dental de prueba (4 profesionales, 10 servicios)
```

## Convenciones de código

- Seguir convenciones Laravel estándar: PSR-4, Eloquent, facades
- Services en `app/Services/`, uno por responsabilidad
- Modelos con `$fillable` explícito, relaciones tipadas (HasMany, BelongsTo)
- Migraciones con `timestampsTz()` (zona horaria importa: Ecuador UTC-5)
- Logs de API van al channel `api` → `Log::channel('api')`
- Config de servicios externos en `config/services.php` con `env()` vars
- No usar emojis en código ni en respuestas al usuario
- **Try-catch obligatorio** en toda operación que pueda fallar (HTTP, DB, API calls). Loggear errores con contexto útil
- **Código conciso** — no sobre-abstraer, no extender archivos innecesariamente. Métodos cortos y con responsabilidad clara
- **Frontend:** React + Inertia + Tailwind CSS + shadcn/ui (pendiente instalar). No tocar landing page a menos que se pida

## Base de datos actual

```
organizations
├── id, name, wa_phone_number (unique), cancellation_hours_min (default 24), timestamps

patients
├── id, organization_id (FK), wa_id (indexed), name, phone_number, timestamps
└── unique: [organization_id, wa_id]

professionals
├── id, organization_id (FK), name, specialty, timestamps

services
├── id, organization_id (FK), name, description, timestamps

professional_service (pivot)
├── professional_id (FK), service_id (FK), duration_minutes, price
└── unique: [professional_id, service_id]

schedules
├── id, professional_id (FK), day_of_week (0-6), start_time, end_time, timestamps

appointments
├── id, organization_id (FK), patient_id (FK), professional_id (FK), service_id (FK)
├── starts_at (UTC), ends_at (UTC), status (confirmed|cancelled), deposit_paid (bool)
├── cancelled_at, cancellation_reason, timestamps
└── index: [professional_id, starts_at, ends_at]

conversations
├── id, phone_number, organization_id (FK), patient_id (FK)
├── conversation_status, handoff_to_human, context (JSON), timestamps
└── unique: [phone_number, organization_id]

conversation_messages
├── id, conversation_id (FK cascade), role (user|assistant), content (text), timestamps
└── index: [conversation_id, created_at]

tool_call_logs
├── id, organization_id, conversation_id, patient_id, tool_name
├── input (JSON), result (JSON), duration_ms, success (bool), error_message, timestamps
```

> **Nota:** Todos los datetimes en BD se almacenan en UTC. La conversion a hora local
> (America/Guayaquil, UTC-5) se hace al presentar al paciente y al recibir input.

## Principio central

El backend es la única fuente de verdad. Claude interpreta intención y mantiene
contexto conversacional, pero nunca inventa horarios, cupos, tarifas ni políticas.
Todo dato operativo sale de una tool call al backend.

## Fase actual: Fase 2 completada, puliendo agente (branch `feature/fase1/start`)

### Fase 1 — completada
1. Migrar `OpenAIService` → `AnthropicService` (tool calling nativo de Anthropic)
2. Resolución de paciente por `wa_id` (auto-registro si no existe)
3. Resolución de tenant por `org_id` (desde número WhatsApp Business entrante)
4. Tools read-only: `get_services`, `get_professionals`, `get_availability`, `list_upcoming_appointments`
5. Memoria estructurada de conversación (context JSON en conversations)
6. Observabilidad: log de cada tool call en `tool_call_logs`

### Fase 2 — completada
1. Tools transaccionales: `confirm_appointment`, `cancel_appointment`, `reschedule_appointment`
2. Politica de cancelacion por tenant (`cancellation_hours_min`, default 24h)
3. Bypass de politica con `deposit_paid=true`
4. Reschedule atomico: cancel old → confirm new → rollback si falla
5. Handoff a humano tras 2 rondas consecutivas de errores en tools
6. Debounce de mensajes WhatsApp (10s ventana via queue + Cache lock/pull): acumula mensajes rapidos y responde una vez
7. System prompt optimizado para respuestas cortas estilo WhatsApp
8. Fecha/hora actual + proximos 7 dias inyectados en system prompt para resolver fechas relativas
9. Seed de clinica dental de prueba: 4 profesionales, 10 servicios, horarios realistas (`DentalClinicSeeder`)

### Problemas detectados en testing (pendientes de arreglar)
- **System prompt necesita mas calidez:** cuando el paciente saluda, el agente debe saludar de vuelta antes de ir a la accion
- **Multiples servicios = una sola cita:** si el paciente pide "limpieza y blanqueamiento", agendar en UNA cita (tomar duracion del servicio mas largo), no sugerir dos citas separadas
- **Token de Meta expira cada 24h:** en modo test, el token temporal caduca. Generar System User Token permanente para produccion

### Pendiente para Fase 3
- Arreglar system prompt (calidez, logica de multi-servicio, calendario correcto)
- Instalar shadcn + MagicUI para UI
- Panel admin con dashboard (React + Inertia + Tailwind + shadcn)
- Recordatorios automaticos de citas (24h y 2h antes)
- Onboarding de tenants desde el panel
- Instalar Playwright para testing E2E
- Eliminar `OpenAIService.php` (legacy sin uso)

## Reglas para Claude Code

- El plan de fases completo está en `NOTES.md` — consultarlo si hace falta contexto del roadmap
- No modificar la landing page (Inertia/frontend) a menos que se pida explícitamente
- No tocar archivos de Auth (Breeze) a menos que sea necesario
- Preferir editar archivos existentes sobre crear nuevos
- Al crear migraciones, usar `timestampsTz()` siempre
- Zona horaria del negocio: America/Guayaquil (UTC-5)
- Idioma del system prompt y respuestas al paciente: español
- Idioma del código (variables, comments, commits): inglés
- Commits en inglés, formato: `type: short description` (feat, fix, refactor, docs, test)
