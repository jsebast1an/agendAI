# Fase 1 -- Tools read-only y cerebro del agente

**Estado:** Completada

## Objetivo

Construir el "cerebro" del agente con datos reales del backend, sin riesgo transaccional. Validar que Claude entiende la conversacion, mantiene contexto y llama tools correctamente antes de habilitar escrituras.

## Que se construyo

### 1. Migracion de OpenAI a Anthropic

Se reemplazo `OpenAIService` (GPT) por `AnthropicService` con tool calling nativo de Anthropic. El contrato hacia el webhook controller no cambio -- el controller sigue llamando a un servicio con la misma firma.

**Por que Anthropic?** El tool calling de Claude es mas estructurado y predecible que el de GPT para este caso de uso. Claude maneja mejor el contexto conversacional en espanol y las instrucciones de tono/estilo.

**Archivo:** `app/Services/AnthropicService.php`

### 2. Resolucion de paciente por wa_id

Al inicio de cada conversacion, se resuelve el paciente usando el `wa_id` que Meta provee en cada webhook. Si el paciente no existe, se crea automaticamente con datos minimos.

**Por que wa_id y no el numero de telefono?** El `wa_id` es un identificador estable provisto por Meta. El numero de telefono puede cambiar de formato segun el pais o el carrier. El `wa_id` es unico por cuenta de WhatsApp.

**Archivo:** `app/Services/PatientResolverService.php`

### 3. Resolucion de tenant por org_id

Cada numero de WhatsApp Business mapea a una organizacion. `TenantResolverService` busca la organizacion por el `wa_phone_number` que viene en el metadata del webhook.

**Archivo:** `app/Services/TenantResolverService.php`

### 4. Tools read-only

Se implementaron 4 tools que Claude puede llamar para obtener informacion:

| Tool | Descripcion | Parametros |
|---|---|---|
| `get_services` | Servicios del consultorio | ninguno (usa org_id del contexto) |
| `get_professionals` | Profesionales, filtrable por servicio | `service_id` (opcional) |
| `get_availability` | Slots disponibles para una fecha | `professional_id`, `service_id`, `date_local` |
| `list_upcoming_appointments` | Citas futuras del paciente | ninguno (usa patient_id del contexto) |

**Como funciona `get_availability`:**
1. Busca los horarios semanales del profesional para el dia de la semana solicitado
2. Obtiene la duracion del servicio desde la tabla pivot `professional_service`
3. Genera slots contiguos dentro de cada bloque horario
4. Elimina los slots que colisionan con citas existentes (status != cancelled)
5. Devuelve la lista de slots disponibles con hora inicio/fin

**Archivo:** `app/Services/AgendaToolsService.php`

### 5. Memoria estructurada de conversacion

La conversacion mantiene un campo `context` (JSON) que persiste entre mensajes:

- `selected_service_id` -- servicio seleccionado
- `selected_professional_id` -- profesional seleccionado
- `preferred_date` -- fecha preferida
- `last_availability_result` -- ultimo resultado de disponibilidad

Este contexto se inyecta en el system prompt para que Claude sepa que se ha discutido previamente sin tener que releer todo el historial.

### 6. Observabilidad

Cada tool call se registra en la tabla `tool_call_logs` con nombre, input, resultado, duracion y si fue exitosa. Los logs de la API van al channel `api` de Laravel.

**Por que una tabla dedicada?** Los archivos de log se rotan y se pierden. Tener una tabla permite consultar patrones de uso, detectar tools que fallan frecuentemente, y medir latencia.

## Modelo de datos creado en esta fase

- `organizations` -- tenants
- `patients` -- pacientes por wa_id
- `professionals` -- profesionales con especialidad
- `services` -- servicios del consultorio
- `professional_service` -- pivot con duracion y precio
- `schedules` -- horarios semanales
- `appointments` -- citas (aunque en Fase 1 solo se leen)
- `tool_call_logs` -- observabilidad

Ver [esquema completo](../database/schema.md).

## Problemas encontrados y como se resolvieron

### Formato de input vacio en Anthropic

Cuando Claude llama una tool sin parametros (como `get_services`), envia `input: []` (array vacio). Pero al reenviar ese bloque como parte del historial, Anthropic espera `input: {}` (objeto vacio). Se resolvio casteando arrays vacios a objetos vacios antes de reenviar.

### Zona horaria en slots de disponibilidad

Inicialmente los slots se generaban en UTC, lo cual confundia al paciente ("Tienes a las 13:00" cuando en realidad son las 8:00 en Ecuador). Se corrigio para que los slots se generen y presenten en hora local (America/Guayaquil).

## Lo que NO entro en esta fase

- Ninguna tool de escritura (no crear, no cancelar citas)
- Recordatorios automaticos
- Panel admin
