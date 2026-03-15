# Guia de inicio

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+ (solo para el frontend/landing)
- Cuenta de Anthropic con API key
- Cuenta de WhatsApp Business con acceso a la API de Meta Graph

## Instalacion

### 1. Clonar el repositorio

```bash
git clone <url-del-repo>
cd agendAI
```

### 2. Instalar dependencias

```bash
composer install
npm install
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar variables de entorno

Editar `.env` con las credenciales necesarias:

```env
# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agendai
DB_USERNAME=tu-usuario
DB_PASSWORD=tu-password

# Queue y cache (necesarios para debounce)
QUEUE_CONNECTION=database
CACHE_STORE=database

# Anthropic (Claude API)
ANTHROPIC_API_KEY=sk-ant-xxxxx
ANTHROPIC_MODEL=claude-sonnet-4-20250514

# WhatsApp Business API (Meta)
WABA_TOKEN=tu-token-de-meta
WABA_PHONE_ID=tu-phone-id
WABA_VERIFY_TOKEN=tu-token-de-verificacion
WABA_TEST_TO=+593XXXXXXXXX
WABA_MEMORY_LIMIT=50
```

**Notas importantes:**
- `QUEUE_CONNECTION` y `CACHE_STORE` deben ser `database` para que el debounce funcione correctamente entre el web server y el queue worker
- `WABA_TEST_TO` es opcional: en entorno local, todos los mensajes salientes se redirigen a este numero
- `WABA_MEMORY_LIMIT` controla cuantos mensajes de historial se envian a Claude (default: 50)

### 5. Crear la base de datos y ejecutar migraciones

```bash
mysql -u root -e "CREATE DATABASE agendai;"
php artisan migrate
```

### 6. Seed de datos de prueba (opcional)

Para cargar una clinica dental de ejemplo con 4 profesionales y 10 servicios:

```bash
php artisan db:seed --class=DentalClinicSeeder
```

Esto crea la organizacion "Clinica Dental Sonrisa" con profesionales, servicios, horarios y tabla de precios completa.

### 7. Levantar el entorno de desarrollo

```bash
composer dev
```

Este comando levanta simultaneamente:
- Servidor PHP (`php artisan serve`)
- Queue worker (`php artisan queue:work`)
- Log viewer (`php artisan pail`)
- Vite dev server (para el frontend)

## Configurar el webhook de WhatsApp

### 1. Exponer el servidor local

Para desarrollo, necesitas una URL publica. Puedes usar ngrok o similar:

```bash
ngrok http 8000
```

### 2. Registrar el webhook en Meta

En la consola de desarrolladores de Meta:

1. Ir a tu app de WhatsApp Business
2. En "Webhooks", configurar la URL de callback:
   - URL: `https://tu-dominio-ngrok.ngrok.io/webhook/whatsapp`
   - Token de verificacion: el mismo valor que pusiste en `WABA_VERIFY_TOKEN`
3. Suscribirse al campo `messages`

### 3. Verificar que funciona

Meta enviara un GET al webhook para verificar. Si el token coincide, la suscripcion se activa.

Luego, enviar un mensaje al numero de WhatsApp Business. Deberia aparecer en los logs:

```bash
php artisan pail --filter="WA inbound"
```

## Estructura de la organizacion de prueba

El seeder crea la siguiente estructura:

| Profesional | Especialidad | Servicios |
|---|---|---|
| Dra. Carmen Martinez | Odontologia General | Consulta ($35), Limpieza ($50), Extraccion Simple ($40), Odontopediatria ($35) |
| Dr. Andres Ramirez | Endodoncia e Implantologia | Consulta ($40), Endodoncia ($180), Implante ($600), Muela del Juicio ($120) |
| Dra. Lucia Vega | Ortodoncia y Estetica | Consulta ($35), Ortodoncia ($80), Blanqueamiento ($150) |
| Dr. Miguel Solano | Periodoncia | Consulta ($40), Periodoncia ($90), Limpieza ($70) |

Cada profesional tiene horarios semanales definidos. Ver [DentalClinicSeeder](../database/seeders/DentalClinicSeeder.php) para detalles.

## Verificar la instalacion

1. Revisar que las migraciones corrieron sin error
2. Revisar que el seeder cargo datos: `php artisan tinker` y luego `Organization::count()` deberia devolver 1
3. Enviar un mensaje al numero de WhatsApp Business y verificar que el agente responde
4. Revisar los logs en `storage/logs/` y el channel `api` para confirmar que las tool calls se estan ejecutando
