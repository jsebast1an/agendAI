# Fase 2 -- Operacion transaccional

**Estado:** Completada

## Objetivo

Habilitar el agendamiento real: crear citas, cancelarlas y reprogramarlas. Tambien mejorar la resiliencia del sistema con debounce de mensajes, handoff a humano y politicas de cancelacion.

## Que se construyo

### 1. Tools transaccionales

Se implementaron 3 tools de escritura:

| Tool | Descripcion | Parametros |
|---|---|---|
| `confirm_appointment` | Crea y confirma una cita | `professional_id`, `service_id`, `start_local` |
| `cancel_appointment` | Cancela una cita existente | `appointment_id`, `reason` |
| `reschedule_appointment` | Reprograma una cita | `appointment_id`, `new_professional_id`, `new_service_id`, `new_start_local` |

**Decision de diseño: sin estado HOLD.** En el MVP se confirma la cita directamente. El riesgo de doble booking es bajo en consultorios pequenos. Se valida conflicto de horario antes de crear, pero no hay reserva temporal. La firma de `confirm_appointment` ya soporta agregar HOLD en el futuro.

**Archivo:** `app/Services/AppointmentService.php`

### 2. Politica de cancelacion por tenant

Cada organizacion tiene un campo `cancellation_hours_min` (default: 24 horas) que define con cuanta anticipacion se puede cancelar o reprogramar una cita.

- Si el paciente intenta cancelar dentro de la ventana: se rechaza con mensaje explicativo
- Si la cita tiene `deposit_paid = true`: se bypasea la politica (el deposito cubre el riesgo)

**Por que bypass con deposito?** En consultorios reales, cuando el paciente pago un deposito, la clinica acepta cancelaciones de ultimo minuto porque ya tiene una garantia economica.

Ver [politica de cancelacion](../features/cancellation-policy.md) para mas detalle.

### 3. Reschedule atomico

Reprogramar es una operacion compuesta: cancelar la cita vieja + confirmar la nueva. Si la confirmacion de la nueva falla (por ejemplo, el slot ya no esta disponible), se restaura la cita original.

```
1. Validar politica de cancelacion
2. Cancelar cita original (status → cancelled)
3. Intentar confirmar nueva cita
4. Si falla → restaurar cita original (status → confirmed)
5. Si exito → devolver datos de la nueva cita
```

**Por que no una transaccion de base de datos?** Se usa un rollback manual (restaurar el status) en vez de `DB::transaction()` porque la operacion involucra logica de validacion intermedia que es mas clara con flujo explicito.

### 4. Handoff a humano

Cuando Claude falla 2 rondas consecutivas en ejecutar tools (todas las tools de una ronda devuelven error), el sistema:

1. Marca la conversacion con `handoff_to_human = true`
2. Envia un mensaje de disculpa al paciente pidiendo que contacte directamente al consultorio
3. Los mensajes siguientes del paciente se ignoran (el webhook devuelve OK sin procesar)

**Por que 2 rondas?** Una sola falla puede ser transitoria. Dos consecutivas indican un problema real que el agente no puede resolver.

Ver [handoff a humano](../features/handoff.md).

### 5. Debounce de mensajes

Los pacientes de WhatsApp envian multiples mensajes rapidos. Sin debounce, cada mensaje generaria una llamada separada a Claude (costosa y redundante).

El sistema implementa una ventana de debounce de 10 segundos:
1. Cada mensaje se acumula en cache
2. Se despacha un job con delay de 10 segundos
3. El job que llega toma un lock, extrae todos los mensajes pendientes, los concatena y los envia como uno solo a Claude
4. Los jobs duplicados encuentran el cache vacio y se descartan

Ver [debounce de mensajes](../features/debounce.md).

### 6. System prompt optimizado

Se reescribio el system prompt para producir respuestas cortas estilo WhatsApp:
- Maximo 2-3 lineas por mensaje
- Una pregunta por mensaje
- Sin emojis, sin listas largas
- Tono directo, calido, profesional

Ademas se inyecta la fecha/hora actual y una referencia de los proximos 7 dias para que Claude resuelva fechas relativas ("manana", "el lunes") sin errores de calculo.

### 7. Fecha actual en system prompt

Se inyecta la fecha y hora actual en zona Ecuador al system prompt, junto con una tabla de los proximos 7 dias con su nombre y fecha. Esto resuelve el problema de que el LLM no sabe que dia es hoy y puede calcular mal "manana" o "el proximo lunes".

## Problemas encontrados y como se resolvieron

### Race conditions en mensajes rapidos

Antes del debounce, dos mensajes rapidos podian generar dos respuestas de Claude simultaneas, a veces contradictorias. Se resolvio con el sistema de debounce (cache + lock + delayed job).

### Nonce vs lock+pull

Inicialmente se implemento un sistema de nonce para el debounce, donde cada mensaje tenia un ID unico y solo el ultimo se procesaba. Esto resulto fragil con multiples workers. Se simplifico a lock + pull atomico del cache.

### Cache driver file vs database

El cache driver `file` no funciona para debounce porque el web server y el queue worker pueden correr en procesos separados que no comparten el filesystem de cache de la misma forma. Se migro a `database` driver.

## Migraciones de esta fase

- `add_cancellation_policy_to_organizations_table` -- campo `cancellation_hours_min`
- `add_deposit_paid_to_appointments_table` -- campo `deposit_paid`

## Lo que NO entro en esta fase

- Recordatorios automaticos de citas
- Panel admin para onboarding de tenants
- Eliminacion de `OpenAIService.php` (legacy)
