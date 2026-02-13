# Project: AgendAI - Teatro San Gabriel

## Objective

Build an AI-powered digital assistant for Teatro San Gabriel (Quito) that operates via WhatsApp and behaves like a real human receptionist.

The assistant must:

- Answer frequently asked questions (parking, prices, schedules, payment methods).
- Inform users about available performances by date.
- Check availability of functions.
- In the future: allow ticket reservations.
- Escalate to a human when necessary.
- Maintain conversational memory (do not restart greetings if the same user sends another message).
- Support multiple concurrent users messaging at the same time.

This is not a simple chatbot.
It must behave like a professional digital receptionist - natural, helpful, and context-aware.

---

## Current Stack

- Backend: Laravel
- Frontend: React + Inertia
- UI: TailwindCSS
- Integration: WhatsApp Cloud API
- LLM: OpenAI API (ChatGPT)
- Database: MySQL

Current state:

- WhatsApp messages reach a Laravel webhook.
- The webhook sends messages to OpenAI and returns a response.
- Conversational memory is stored in the database (conversations + messages).
- The assistant loads only the last N messages into context (default 10) but keeps all messages for analytics.
- No tools / structured actions are implemented yet.
- Database schema exists for conversation memory (no plays/functions tables yet).
- No functions or ticket system implemented yet.

---

## Initial Scope (MVP)

Phase 1:

- Add fixed business context (theater information) to the model.
- Implement basic conversation memory per phone number.
- Create database schema for:
  - Plays
  - Functions (date, time, price, availability)
- Allow users to query performances by date.

Not included in MVP:

- Online payments.
- Complex reservation workflows.
- Advanced automation.
- Over-engineering.

Keep the architecture simple and clean.

---

## Architecture Principles

- Keep the system simple and maintainable.
- The LLM interprets natural language.
- Laravel controls all business logic and database operations.
- The model must never generate raw SQL.
- Business rules live strictly in the backend.
- The assistant must feel human and professional.
- The system must be scalable for multiple simultaneous conversations.

---

## Latest Status (February 6, 2026)

- WhatsApp webhook is working with ngrok in dev.
- Memory is stored in DB tables:
  - `conversations` (per phone number)
  - `conversation_messages` (all messages kept)
- The app builds AI context from the most recent N messages.
- Dev override can send replies to a test number via `WABA_TEST_TO`.

---

## Long-Term Vision

Transform this solution into a reusable AI receptionist platform for:

- Theaters
- Cultural centers
- Academies
- Event venues

This project should evolve into a replicable product for small and medium businesses.
