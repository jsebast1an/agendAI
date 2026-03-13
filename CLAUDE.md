# AgendAI — Contexto para Claude Code

## Qué es este proyecto

Recepcionista digital 24/7 para consultorios médicos en Ecuador.
Atiende pacientes por WhatsApp: responde dudas, consulta disponibilidad y agenda citas.
Multi-tenant — un backend sirve a múltiples consultorios.

## Stack

- **Backend:** Laravel 12 (PHP 8.2+) + MySQL
- **Frontend:** Inertia.js (landing page, no relevante para el agente)
- **LLM:** Migrando de OpenAI (GPT) → Claude API (Anthropic) con tool calling nativo
- **Canal:** WhatsApp Business API (Meta Graph API v22.0)
- **Testing:** PHPUnit, Laravel Pint (linter)
- **Dev server:** `composer dev` (levanta server + queue + pail + vite)

## Estructura clave

```
app/
├── Http/Controllers/
│   └── WhatsappWebhookController.php   # Webhook principal (POST/GET /api/webhook/whatsapp)
├── Models/
│   ├── Conversation.php                # phone_number → conversación activa
│   └── ConversationMessage.php         # Mensajes (role: user|assistant)
├── Services/
│   ├── OpenAIService.php               # A REEMPLAZAR por AnthropicService en Fase 1
│   └── WhatsappService.php             # Envía mensajes vía Meta Graph API
config/services.php                     # Config de openai, waba (tokens, phone_id, etc.)
routes/api.php                          # POST|GET /webhook/whatsapp
database/migrations/                    # conversations + conversation_messages
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
- **Frontend:** Tailwind CSS (cuando aplique). No tocar frontend a menos que se pida

## Base de datos actual

```
conversations
├── id, phone_number (unique, indexed), conversation_status, handoff_to_human, timestamps

conversation_messages
├── id, conversation_id (FK cascade), role (user|assistant), content (text), timestamps
└── index: [conversation_id, created_at]
```

## Principio central

El backend es la única fuente de verdad. Claude interpreta intención y mantiene
contexto conversacional, pero nunca inventa horarios, cupos, tarifas ni políticas.
Todo dato operativo sale de una tool call al backend.

## Fase actual: Fase 1 (branch `feature/fase1/start`)

### Objetivo
Construir el cerebro del agente con datos reales, sin riesgo transaccional.
Solo tools read-only.

### Tareas de Fase 1
1. Migrar `OpenAIService` → `AnthropicService` (tool calling nativo de Anthropic)
2. Resolución de paciente por `wa_id` (auto-registro si no existe)
3. Resolución de tenant por `org_id` (desde número WhatsApp Business entrante)
4. Tools read-only: `get_services`, `get_professionals`, `get_availability`, `list_upcoming_appointments`
5. Memoria estructurada de conversación (servicio, profesional, fecha, último resultado)
6. Observabilidad: log de cada tool call

### Lo que NO entra en Fase 1
- Tools de escritura (no crear, no cancelar citas)
- Recordatorios automáticos
- Panel admin

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
