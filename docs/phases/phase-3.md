# Fase 3 -- Automatizaciones y operacion

**Estado:** Pendiente de desarrollo

## Objetivo

Agregar automatizaciones que mejoren la experiencia del paciente y faciliten el onboarding de nuevos consultorios. Tambien limpiar deuda tecnica acumulada.

## Que se planea construir

### 1. Recordatorios automaticos de citas

Enviar mensajes automaticos al paciente antes de su cita:
- **24 horas antes:** recordatorio con fecha, hora, profesional y servicio
- **2 horas antes:** recordatorio breve

**Implementacion planeada:**
- Laravel Task Scheduling para revisar citas proximas cada hora
- Queue job dedicado para enviar cada recordatorio
- Manejar fallos de entrega de WhatsApp (reintento o log)
- Zona horaria: Ecuador (UTC-5) para calcular las ventanas

**Consideraciones:**
- No enviar recordatorios para citas ya canceladas
- No enviar duplicados si el scheduler corre mas de una vez
- Registrar en log si el mensaje no se pudo entregar

### 2. Panel admin basico

Un panel web minimo para que el dueño del consultorio pueda:
- Crear y editar organizacion (nombre, numero WhatsApp, politica de cancelacion)
- Agregar/editar profesionales y sus horarios
- Agregar/editar servicios y precios
- Ver citas del dia

**Por que un panel?** Actualmente, crear un nuevo tenant requiere intervenir en la base de datos directamente o correr un seeder. Esto no escala para onboarding de clientes reales.

### 3. Eliminacion de OpenAIService

`OpenAIService.php` es codigo legacy que no se usa desde la migracion a Anthropic en Fase 1. Debe eliminarse para reducir confusion.

### 4. Expansion a otros canales (exploratorio)

Evaluar si el mismo agente puede atender por otros canales ademas de WhatsApp (Instagram, Telegram, web chat). Esto requiere abstraer la capa de canal.

## Decisiones pendientes

- Definir TTL de conversaciones inactivas (cuanto tiempo se guarda el contexto antes de reiniciar)
- Evaluar migracion de MySQL a PostgreSQL antes del primer cliente real
- Definir proveedor definitivo de WhatsApp API (Meta directo vs Twilio vs 360dialog)
