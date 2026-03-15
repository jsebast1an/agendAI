# AgendAI

## Que es AgendAI

AgendAI es una recepcionista digital 24/7 para consultorios medicos en Ecuador. Atiende pacientes por WhatsApp: responde dudas, consulta disponibilidad y agenda citas de forma autonoma.

El sistema es **multi-tenant** -- un mismo backend sirve a multiples consultorios. Cada consultorio (organizacion) tiene sus propios profesionales, servicios, horarios y politicas.

## Para quien es

- **Consultorios medicos y dentales** que quieren atender pacientes fuera de horario de oficina
- **Clinicas pequenas** que no pueden costear personal 24/7 en recepcion
- **Profesionales independientes** que necesitan automatizar su agenda sin perder el trato humano

## Que hace hoy

- Recibe mensajes de pacientes por WhatsApp Business API
- Entiende intencion conversacional usando Claude (Anthropic) con tool calling nativo
- Consulta servicios, profesionales y disponibilidad en tiempo real
- Agenda, cancela y reprograma citas con validacion de conflictos
- Aplica politica de cancelacion configurable por consultorio
- Escala a un humano cuando detecta que no puede resolver la situacion
- Acumula mensajes rapidos (debounce) para responder una sola vez

## Principio central

El backend es la unica fuente de verdad. Claude interpreta intencion y mantiene contexto conversacional, pero **nunca inventa horarios, cupos, tarifas ni politicas**. Todo dato operativo sale de una tool call al backend.

## Stack tecnologico

| Componente | Tecnologia |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) + MySQL |
| LLM | Claude API (Anthropic) con tool calling |
| Canal | WhatsApp Business API (Meta Graph API v22.0) |
| Queue | Laravel Queue (database driver) |
| Cache | Database driver |
| Frontend | Inertia.js (landing page, no relevante para el agente) |

## Estado actual

Las **Fases 1 y 2** estan completadas. El sistema puede atender pacientes, consultar disponibilidad, agendar citas, cancelarlas y reprogramarlas. Ver detalles en:

- [Fase 1](phases/phase-1.md) -- Tools read-only y cerebro del agente
- [Fase 2](phases/phase-2.md) -- Operacion transaccional
- [Fase 3](phases/phase-3.md) -- Automatizaciones (pendiente)

## Documentacion

- [Arquitectura del sistema](architecture.md)
- [Guia de inicio](getting-started.md)
- [Seguridad](security.md)
- [Despliegue](deployment.md)

### API
- [Webhook de WhatsApp](api/webhook.md)
- [Integracion con Anthropic](api/anthropic-integration.md)
- [API de WhatsApp (Meta)](api/whatsapp-api.md)

### Funcionalidades
- [Multi-tenancy](features/multi-tenant.md)
- [Flujo de agendamiento](features/appointment-flow.md)
- [Debounce de mensajes](features/debounce.md)
- [Politica de cancelacion](features/cancellation-policy.md)
- [Handoff a humano](features/handoff.md)

### Base de datos
- [Esquema](database/schema.md)
- [Migraciones](database/migrations.md)
