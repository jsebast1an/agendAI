# Proyecto: AgendAI – Teatro San Gabriel

## Objetivo

Construir un asistente digital con IA para el Teatro San Gabriel (Quito) que funcione vía WhatsApp y responda como una persona real.

El asistente debe:

- Responder preguntas frecuentes (parqueadero, precios, horarios, métodos de pago).
- Informar qué funciones hay según fecha.
- Permitir consultar disponibilidad.
- En el futuro: permitir reservas.
- Escalar a humano si no puede resolver algo.

No es un bot simple. Debe comportarse como recepcionista digital.

---

## Stack actual

- Backend: Laravel
- Frontend: React + Inertia
- UI: TailwindCSS
- Integración: WhatsApp Cloud API
- LLM: OpenAI API (ChatGPT)
- Base de datos: MySQL

Actualmente:
- WhatsApp ya envía mensajes al webhook.
- El webhook ya llama a ChatGPT y responde.
- No hay memoria conversacional.
- No hay herramientas (tools) implementadas.
- No hay sistema de funciones/stock todavía.

---

## Alcance inicial (MVP)

Fase 1:
- Agregar contexto fijo del teatro al modelo.
- Implementar memoria básica por número de teléfono.
- Crear estructura base para funciones (tabla plays + functions).
- Permitir consulta de funciones por fecha.

No implementar pagos todavía.
No implementar reservas complejas todavía.
Mantener arquitectura simple.

---

## Principios del proyecto

- Arquitectura simple y clara.
- El modelo interpreta lenguaje natural.
- Laravel controla base de datos e inventario.
- El modelo nunca genera SQL.
- Las reglas de negocio viven en backend.
- El asistente debe sentirse humano.

---

## Objetivo final

Convertir este sistema en un producto replicable para otros negocios (teatros, academias, centros culturales).

