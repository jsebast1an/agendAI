# Project: AgendAI - Recepcionista de Consultorio (Ecuador)

> **Nuevo rumbo (actual):**
> AgendAI evoluciona a una recepcionista digital 24/7 para consultorios médicos,
> enfocada en agendamiento, reprogramación, cancelación y confirmación de citas,
> con políticas de privacidad estrictas y ejecución backend-first mediante tools.

## Identidad y objetivo operativo

- La asistente es parte del equipo del consultorio.
- Comunicación en español (Ecuador), tono profesional, cálido y claro.
- Objetivo principal: atender dudas de agendandamiento, profesionales y horarios disponible, reprogramar y cancelar citas con la menor fricción posible.
- Objetivo secundario: confirmar citas y reducir inasistencias con recordatorios.
- Cuando el caso sea complejo o exista molestia del paciente, se ofrece pase a humano.

---

## Reglas críticas de privacidad (MVP no negociable)

- No pedir ni almacenar detalles clínicos por chat (síntomas, diagnósticos, tratamiento).
- Solo solicitar datos mínimos operativos:
  - nombre,
  - teléfono,
  - servicio,
  - fecha/hora preferida.
- Si piden diagnóstico o tratamiento por chat:
  - aclarar que por este canal no se diagnostica,
  - ofrecer agendar consulta.

---

## Principios de arquitectura

- **Backend-first truth:** horarios, disponibilidad, tarifas y políticas salen de herramientas/backend, nunca inventados por el modelo.
- **LLM como capa conversacional:** interpreta intención y mantiene contexto, pero no ejecuta reglas críticas por sí solo.
- **Contratos explícitos:** entradas/salidas de herramientas tipadas y validadas.
- **Observabilidad:** trazabilidad por conversación, tool calls y errores.
- **Escalabilidad:** soporte para múltiples conversaciones concurrentes.
- **Compatibilidad futura:** agregar complejidad por capas sin romper contratos base.

---

## Herramientas objetivo (contratos funcionales)

Estas funciones definen la operación del flujo de citas:

- `get_services(org_id)`
- `get_professionals(org_id, service_id?)`
- `get_availability(professional_id, service_id, date_local)`
- `hold_appointment(patient_id, professional_id, service_id, start_local)`
- `confirm_appointment(appointment_id)`
- `cancel_appointment(appointment_id, reason)`
- `list_upcoming_appointments(patient_id)`

Reglas operativas asociadas:

- No inventar cupos, horarios, tarifas ni políticas.
- No confirmar cita sin antes crear HOLD y luego confirmar.
- Si no hay disponibilidad, proponer 2–3 alternativas cercanas.
- Si una herramienta falla: disculpa breve, reintento y, si persiste, pase a humano.

---

## Plan por fases (reformateado)

## Fase 0 (estado actual del proyecto)

**Qué incluye**

- Webhook WhatsApp operativo con loop conversacional básico.
- WhatsappController and OpenAIService
- Persistencia de conversaciones y memoria corta por paciente.
- Reglas de privacidad definidas y tono de recepcionista ya establecido.
- Handoff a humano como salida para casos complejos.
- Sin integración de tools de agenda en producción todavía.
- Basic tables and models: `conversation` and `conversationMessages`

**Por qué va en Fase 0**

- Documenta con precisión la línea base actual del sistema.
- Evita planificar sobre supuestos y permite medir progreso real.
- Reduce riesgo al separar claramente "lo que ya existe" de "lo que sigue".

**Compatibilidad futura**

- Reutiliza el flujo actual de WhatsApp como base de integración.
- Mantiene la conversación y memoria como contexto para futuras tools.

---

## Fase 1 (tools read-only + cerebro/memoria del agente)

**Qué incluye**

- Integración de tools read-only:
  - `get_services(org_id)`
  - `get_professionals(org_id, service_id?)`
  - `get_availability(professional_id, service_id, date_local)`
  - `list_upcoming_appointments(patient_id)`
- Orquestación de tool-calling para responder con datos reales del backend.
- Memoria estructurada de trabajo por conversación:
  - servicio seleccionado
  - profesional seleccionado
  - preferencia de fecha/hora
  - último resultado de disponibilidad consultada
- Desambiguación mínima para avanzar sin fricción (una pregunta corta cuando falte precisión).
- Observabilidad base de decisiones:
  - logging de tool calls
  - tiempos de respuesta
  - errores por flujo

**Por qué va en Fase 1**

- Construye primero el "cerebro" del agente con datos reales sin riesgo transaccional.
- Baja el riesgo de inventar horarios/cupos/precios antes de permitir escrituras.
- Valida comprensión conversacional y memoria antes de ejecutar acciones de agenda.

**Compatibilidad futura**

- Contratos read-only estables que luego consumen los flujos de escritura.
- La memoria estructurada se extiende a hold/confirm/cancel sin rediseño.

---

## Fase 2 (operación transaccional y automatizaciones)

**Qué incluye**

- Integración de tools de escritura:
  - `hold_appointment(patient_id, professional_id, service_id, start_local)`
  - `confirm_appointment(appointment_id)`
  - `cancel_appointment(appointment_id, reason)`
- Flujo obligatorio `HOLD -> CONFIRM` para cualquier cita nueva.
- Reprogramación y cancelación end-to-end con validación en backend.
- Confirmación previa y recordatorios automáticos (24h y 2h).
- Política de cancelación configurable por organización (X horas).

**Por qué va en Fase 2**

- Las operaciones de escritura requieren más robustez y control de errores.
- Depende de una capa read-only y memoria ya probada en producción.
- Reduce retrabajo al construir sobre contratos y contexto ya estabilizados.

**Compatibilidad futura**

- Evolución por capas sobre contratos existentes, sin romper integraciones.
- Base lista para sumar dashboard operativo y expansión a otros canales.

---

## Estado actual esperado tras este update

- El proyecto queda definido como recepcionista de consultorio médico (Ecuador).
- Se consolida un enfoque 100% orientado a operación de citas médicas.
- El documento prioriza operación de citas, privacidad y roadmap por fases.
