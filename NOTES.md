# AgendAI — Plan de fases

> Este documento es la fuente de verdad del plan de desarrollo de AgendAI.
> Está escrito para que Claude Code entienda el contexto completo del proyecto
> y pueda ayudar a implementar cada fase sin ambigüedad.

---

## Contexto del proyecto

AgendAI es una recepcionista digital 24/7 para consultorios médicos en Ecuador.
Atiende pacientes por WhatsApp: responde dudas, consulta disponibilidad y agenda citas.
El sistema es multi-tenant — un mismo backend sirve a múltiples consultorios.

**Stack:**
- Backend: Laravel (PHP) + MySQL
- LLM: Claude API (Anthropic) con tool calling nativo
- Canal: WhatsApp Business API
- Identificación de paciente: `wa_id` (ID estable provisto por Meta)
- Identificación de tenant: `org_id` resuelto desde el número WhatsApp Business entrante

**Principio central:**
El backend es la única fuente de verdad. Claude interpreta intención y mantiene
contexto conversacional, pero nunca inventa horarios, cupos, tarifas ni políticas.
Todo dato operativo sale de una tool call al backend.

---

## Arquitectura cognitiva — cómo debe "pensar" AgendAI

Un recepcionista humano no solo ejecuta tareas: procesa información con una
estructura mental específica que el agente debe replicar. El objetivo no es
parecer futurista — es ser predecible, claro y resolutivo. Cuando el paciente
deja de notar que habla con una IA, no es magia técnica: es que el sistema
replicó la lógica humana básica.

### Los tres niveles de memoria que debe mantener

**Memoria de corto plazo — lo que está pasando ahora**
Lo que el paciente acaba de preguntar o decir. El agente debe responder
en función de eso, sin perder el hilo ni reiniciar el contexto.

**Memoria situacional — el evento en curso**
Qué está intentando hacer el paciente en esta conversación: ¿agendar?
¿cancelar? ¿consultar disponibilidad? Esa intención debe mantenerse activa
hasta que se resuelva o el paciente la cambie explícitamente.

**Criterio lógico — inferencia de contexto implícito**
Si el paciente pregunta "¿cuánto cuesta?" sin mencionar un servicio,
el agente infiere que se refiere al último servicio discutido en la conversación.
No pregunta de nuevo algo que ya fue establecido.

### Los tres pilares operativos

**Coherencia conversacional**
El agente no reinicia, no contradice respuestas anteriores y no pierde
el hilo entre mensajes. Si dijo que hay disponibilidad a las 10am, no puede
decir tres mensajes después que no hay horarios disponibles.

**Control narrativo**
La conversación siempre tiene dirección. El agente guía al paciente hacia
la resolución — no solo responde, conduce. Si el paciente se desvía o
se bloquea, el agente retoma el flujo con una pregunta concreta.

**Estabilidad emocional del sistema**
Tono constante en toda la conversación: profesional, cálido, claro.
Ni robótico ni coloquial. El paciente no quiere un chatbot gracioso —
quiere a alguien confiable. El tono no cambia si el paciente se molesta,
si hay un error técnico o si la conversación se alarga.

### Humildad lógica — límite no negociable
Un recepcionista humano no inventa información cuando no sabe: pregunta o escala.
El agente debe hacer lo mismo. Si no tiene el dato, llama la tool correspondiente.
Si la tool falla, lo dice y escala. Nunca rellena con suposiciones.
Inventar información para "salir del paso" rompe la confianza del sistema completo.

---

## Fase 0 — Línea base actual ✅

### Qué existe hoy
- Webhook de WhatsApp operativo con loop conversacional básico
- `WhatsappController` + `OpenAIService` (pendiente migrar a `AnthropicService`)
- Persistencia de conversaciones y memoria corta por paciente
- Reglas de privacidad y tono de recepcionista definidos en system prompt
- Handoff a humano para casos complejos
- Tablas base: `conversations`, `conversation_messages`

### Lo que NO existe aún
- Integración de tools de agenda
- Multi-tenancy (`org_id`)
- Identificación formal de paciente por `wa_id`
- Tool calling con Claude API

### Notas para Claude Code
- El `OpenAIService` actual hace llamadas básicas a GPT. La migración a Anthropic
  se hace al inicio de Fase 1, no antes.
- No tocar la lógica del webhook ni la persistencia de conversaciones hasta Fase 1.

---

## Fase 1 — Tools read-only + cerebro del agente

### Objetivo
Construir el "cerebro" del agente con datos reales del backend, sin riesgo
transaccional. Validar que Claude entiende la conversación, mantiene contexto
y llama tools correctamente antes de habilitar escrituras.

### Qué incluye

**1. Migración a Claude API**
- Reemplazar `OpenAIService` por `AnthropicService`
- Implementar tool calling nativo de Anthropic
- Mantener el mismo contrato hacia `WhatsappController` — el controlador no debe cambiar

**2. Resolución de paciente**
- Al inicio de cada conversación, resolver `patient_id` desde `wa_id`
- Si el paciente no existe: registro automático con datos mínimos (nombre, teléfono)
- El `wa_id` viene en cada webhook de WhatsApp — usarlo como identificador estable

**3. Resolución de tenant**
- Cada número de WhatsApp Business mapea a un `org_id`
- Crear `TenantResolverService` que resuelva `org_id` desde el webhook entrante
- Todas las tool calls deben incluir `org_id`

**4. Tools read-only a implementar**
```
get_services(org_id)
  → devuelve lista de servicios disponibles del consultorio

get_professionals(org_id, service_id?)
  → devuelve profesionales disponibles, opcionalmente filtrados por servicio

get_availability(professional_id, service_id, date_local)
  → devuelve slots disponibles para una fecha dada (zona horaria Ecuador, UTC-5)

list_upcoming_appointments(patient_id)
  → devuelve citas futuras del paciente
```

**5. Memoria estructurada de conversación**

Guardar en la sesión activa de la conversación:
```
- servicio_seleccionado
- profesional_seleccionado
- fecha_hora_preferida
- ultimo_resultado_disponibilidad
```
Esta memoria persiste entre mensajes de la misma conversación y se usa
como contexto en cada llamada a Claude. Se limpia al cerrar la conversación.

**6. Observabilidad base**
- Log de cada tool call: nombre, parámetros, resultado, tiempo de respuesta
- Log de errores por flujo con contexto de conversación
- Sin dashboard por ahora — logs en archivo o tabla es suficiente

### Reglas de comportamiento en Fase 1
- Si no hay disponibilidad: proponer 2-3 fechas alternativas cercanas
- Si una tool falla: disculpa breve, un reintento automático, si persiste → handoff a humano
- Desambiguación mínima: una sola pregunta corta cuando falte información, no un formulario

### Lo que NO entra en Fase 1
- Ninguna tool de escritura (no crear, no cancelar citas)
- Recordatorios automáticos
- Panel admin

---

## Fase 2 — Operación transaccional

### Objetivo
Habilitar el agendamiento real. Depende de Fase 1 estable en producción.
No arrancar esta fase hasta que Fase 1 esté validada con usuarios reales.

### Qué incluye

**1. Tools de escritura a implementar**
```
confirm_appointment(patient_id, professional_id, service_id, start_local)
  → crea y confirma la cita directamente (sin HOLD en MVP)
  → devuelve appointment_id y resumen de la cita

cancel_appointment(appointment_id, reason)
  → cancela una cita existente
  → valida política de cancelación del org antes de proceder
```

> **Nota MVP:** El flujo HOLD → CONFIRM fue simplificado.
> En MVP se confirma directamente. El riesgo de doble booking es bajo
> en consultorios pequeños. HOLD se puede agregar en el futuro
> sin romper el contrato — `confirm_appointment` ya tiene la firma correcta.

**2. Reprogramación**
- Reprogramar = cancelar cita existente + confirmar nueva
- Validar política de cancelación antes de proceder
- El agente debe confirmar con el paciente antes de ejecutar ambas operaciones

**3. Política de cancelación por tenant**
- Configurable por `org_id`: mínimo X horas de anticipación para cancelar
- Si el paciente intenta cancelar fuera de política: informar y ofrecer handoff a humano

**4. Control de errores robusto**
- Reintento automático en fallo de tool
- Si persiste: handoff a humano con resumen del estado de la conversación
- Nunca dejar al paciente sin respuesta

### Lo que NO entra en Fase 2
- Recordatorios automáticos (van en Fase 3)
- Panel admin o dashboard

---

## Fase 3 — Automatizaciones y operación (pendiente de diseño detallado)

### Qué se planea incluir
- Recordatorios automáticos de citas: 24h y 2h antes
  - Implementar con Laravel Task Scheduling + Queue
  - Considerar fallos de entrega WhatsApp (reintento o log)
  - Zona horaria: Ecuador (UTC-5) en todos los cálculos
- Panel admin básico para onboarding de nuevos tenants
  - Crear organización, profesionales y servicios sin intervención técnica
- Expansión a otros canales (a definir)

---

## Decisiones pendientes

- [ ] Migrar `OpenAIService` → `AnthropicService` (inicio de Fase 1)
- [ ] Definir schema completo de tablas con `org_id` y `wa_id`
- [ ] Decidir proveedor WhatsApp API: Meta directo vs Twilio vs 360dialog
- [ ] Definir TTL de conversaciones inactivas (¿cuánto tiempo guarda el contexto?)
- [ ] Definir política de cancelación por defecto para nuevos tenants
- [ ] Evaluar migración MySQL → PostgreSQL antes del primer cliente real
