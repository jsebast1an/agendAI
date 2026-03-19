# AgendAI — Contexto para Claude Code

## Qué es este proyecto

Recepcionista digital 24/7 para consultorios médicos en Ecuador.
Atiende pacientes por WhatsApp: responde dudas, consulta disponibilidad y agenda citas.
Multi-tenant — un backend sirve a múltiples consultorios.

## Stack

- **Backend:** Laravel 12 (PHP 8.2+) + MySQL
- **Frontend:** Inertia.js + React 18 + Tailwind CSS v4 (landing page + admin dashboard en construccion)
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
.claude/agents/prompt-tester.md        # Agent para simular conversaciones de pacientes y testear el system prompt
.claude/agents/docs-generator.md       # Agent para generar documentacion del proyecto
.claude/rules/security.md              # Reglas de seguridad obligatorias para Claude Code
docs/                                   # Documentacion generada del proyecto
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
- **Frontend:** React + Inertia + Tailwind CSS v4. No tocar landing page a menos que se pida

## Base de datos actual

```
organizations
├── id, name, wa_phone_number (unique), cancellation_hours_min (default 24), timestamps

patients
├── id, organization_id (FK), wa_id (indexed), name, phone_number, cedula (nullable), timestamps
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

## Fase actual: Fase 3 en curso — Admin Dashboard (branch `feature/fase1/start`)

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
6. Debounce de mensajes WhatsApp (10s ventana via queue + Cache lock/pull)
7. System prompt optimizado: respuestas cortas, calidez, fecha/hora inyectada
8. Tool `update_patient` para guardar nombre y cedula del paciente antes de confirmar cita
9. Seed de clinica dental: 4 profesionales, 10 servicios, horarios realistas (`DentalClinicSeeder`)
10. Tailwind CSS migrado de v3 a v4

### Fase 3 — en curso
#### Completado
- Migracion Tailwind CSS v3 → v4
- Agentes Claude Code: `prompt-tester`, `docs-generator`
- Reglas de seguridad: `.claude/rules/security.md`
- Documentacion generada en `docs/`

#### Pendiente
- Panel admin con dashboard (React + Inertia + Tailwind v4): metricas, citas, conversaciones
- Recordatorios automaticos de citas (24h y 2h antes)
- Onboarding de tenants desde el panel
- Playwright para testing E2E
- Arreglar system prompt: multi-servicio en una sola cita (usar duracion del servicio mas largo)
- Eliminar `OpenAIService.php` (legacy sin uso)

### Notas de produccion
- **Token de Meta expira cada 24h** en modo test. Renovar desde Meta Developers antes de testear
- **CACHE_STORE=database** (o file) es obligatorio — `array` no persiste entre procesos
- **Supervisor** requerido en produccion para mantener `queue:work` activo

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
