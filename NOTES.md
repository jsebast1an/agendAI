# Project: AgendAI – Teatro San Gabriel

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
It must behave like a professional digital receptionist — natural, helpful, and context-aware.

---

## Current Stack

- Backend: Laravel
- Frontend: React + Inertia
- UI: TailwindCSS
- Integration: WhatsApp Cloud API
- LLM: OpenAI API (ChatGPT)
- Database: MySQL (not yet implemented)

Current state:

- WhatsApp messages reach a Laravel webhook.
- The webhook sends messages to OpenAI and returns a response.
- There is no conversational memory.
- No tools / structured actions are implemented.
- No database schema exists yet.
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

## Long-Term Vision

Transform this solution into a reusable AI receptionist platform for:

- Theaters
- Cultural centers
- Academies
- Event venues

This project should evolve into a replicable product for small and medium businesses.
