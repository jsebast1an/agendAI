# Contexto del Proyecto - AgendAI

1. Estado actual:
- El webhook de WhatsApp ya recibe mensajes y responde mediante OpenAI.
- Existe memoria conversacional en base de datos con `conversations` y `conversation_messages`.
- El contexto que se envía al modelo usa los ultimos N mensajes (por defecto 10), manteniendo historial completo para analitica.
- Aun no estan implementadas tools de dominio ni acciones estructuradas (showtimes, disponibilidad, precios, FAQ desde backend).
- La direccion del producto ya esta alineada a una recepcionista IA robusta tipo Manus (backend-first, deterministica, auditable, con logica).

2. Stack:
- Backend: Laravel
- Frontend: React + Inertia
- UI: TailwindCSS
- Integracion: WhatsApp Cloud API
- LLM: OpenAI API
- Base de datos: MySQL

3. Objetivo del agente:
- Operar como recepcionista digital profesional del Teatro San Gabriel (Quito) por WhatsApp.
- Responder FAQs (parqueadero, pagos, ubicacion, etc.).
- Informar funciones disponibles por fecha.
- Consultar disponibilidad de funciones/asientos.
- No es un robot. Tiene que parecer y actuar como humano.
- Confirmar y gestionar reservas (con validaciones; pre-reserva solo para clientes confiables).
- Escalar a humano cuando corresponda (enojo, peticion compleja, confusion repetida).
- Mantener memoria conversacional y soportar multiples usuarios concurrentes.
- Un agente confiable, natural y contextual que combine memoria conversacional, toma de decisiones guiada y ejecución segura de acciones, permitiendo atender múltiples usuarios simultáneamente con una experiencia profesional, coherente y escalable.

4. Flujo actual:
- Usuario escribe por WhatsApp.
- Meta/WhatsApp envia webhook a Laravel.
- Laravel recupera memoria reciente de la conversacion.
- Laravel arma prompt/contexto y consulta OpenAI.
- OpenAI devuelve respuesta textual.
- Laravel responde al usuario por WhatsApp.

5. Meta inmediata:
- Construir la arquitectura de base de datos para habilitar tools read-only en Fase 1.
- Implementar contratos y consultas deterministicas para:
  - `get_showtimes(date)`
  - `get_available_seats(function_id)`
  - `get_ticket_prices(function_id)`
  - `get_faq(topic)`
- Proponer/Definir/crear tablas de dominio base y soporte para estas tools, considerar todos los escenarios para que el agente de un buen uso a la base de datos y que sea escalable.
- Mantener el principio: respuestas criticas solo desde backend como fuente de verdad.

6. Arquitectura deseada:

LLM = cerebro
Tools + Skills = manos
Laravel = sistema nervioso

WhatsApp
   ↓
Webhook Laravel
   ↓
Conversation Manager
   ↓
LLM (decide acción)
   ↓
Tool Executor (DB / actions)
   ↓
Response Builder
   ↓
WhatsApp
