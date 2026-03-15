# Integracion con WhatsApp (Meta Graph API)

## Vision general

AgendAI usa la **WhatsApp Business API** de Meta (Graph API v22.0) para enviar y recibir mensajes. La comunicacion es bidireccional:

- **Entrada:** Meta envia webhooks al backend cuando un paciente envia un mensaje
- **Salida:** El backend llama a la Graph API para enviar respuestas

**Archivo principal:** `app/Services/WhatsappService.php`

## Envio de mensajes

El servicio tiene un unico metodo `sendText()` que envia mensajes de texto:

```
POST https://graph.facebook.com/v22.0/{WABA_PHONE_ID}/messages
Authorization: Bearer {WABA_TOKEN}
Content-Type: application/json

{
    "messaging_product": "whatsapp",
    "to": "593XXXXXXXXX",
    "type": "text",
    "text": {
        "body": "Hola, buen dia! En que te puedo ayudar?"
    }
}
```

### Modo de prueba

En entorno local/development, todos los mensajes salientes se redirigen al numero configurado en `WABA_TEST_TO`. Esto evita enviar mensajes accidentales a pacientes reales durante el desarrollo.

```php
$to = app()->environment('local', 'development')
    ? (config('services.waba.test_to') ?: $to)
    : $to;
```

## Recepcion de mensajes

La recepcion se maneja en `WhatsappWebhookController`. Ver [webhook](webhook.md) para detalles del formato de payload y flujo de procesamiento.

## Configuracion

```env
WABA_TOKEN=tu-token-de-acceso-de-meta
WABA_PHONE_ID=tu-phone-number-id
WABA_VERIFY_TOKEN=un-string-secreto-para-verificacion
WABA_TEST_TO=+593XXXXXXXXX
WABA_MEMORY_LIMIT=50
```

| Variable | Descripcion |
|---|---|
| `WABA_TOKEN` | Token de acceso permanente de la WhatsApp Business API |
| `WABA_PHONE_ID` | ID del numero de telefono en la plataforma de Meta (no es el numero en si) |
| `WABA_VERIFY_TOKEN` | String secreto compartido entre tu servidor y Meta para verificar el webhook |
| `WABA_TEST_TO` | Numero al que se redirigen mensajes en entorno local (opcional) |
| `WABA_MEMORY_LIMIT` | Cuantos mensajes de historial se envian a Claude por conversacion |

## Limitaciones actuales

- Solo se procesan mensajes de **texto**. Imagenes, audio, stickers y documentos se registran como `[Contenido no textual]`
- No se envian mensajes con formato enriquecido (botones, listas, templates)
- No se manejan estados de lectura ni typing indicators
- El token de acceso debe ser permanente (los tokens temporales expiran en 24h)

## Obtencion del token permanente

1. Crear una app en [Meta for Developers](https://developers.facebook.com/)
2. Agregar el producto "WhatsApp"
3. En la seccion de WhatsApp Business, generar un token de acceso permanente
4. Copiar el Phone Number ID (no es el numero de telefono, es un ID interno de Meta)
5. Configurar las variables de entorno

## Consideraciones de costo

Meta cobra por mensaje enviado segun el tipo de conversacion:
- **Business-initiated:** el negocio inicia la conversacion (mas caro)
- **User-initiated:** el usuario inicia la conversacion (mas barato, ventana de 24h)

En el flujo de AgendAI, el paciente siempre inicia la conversacion, por lo que las respuestas dentro de las 24 horas son mas economicas. Los recordatorios automaticos (Fase 3) seran business-initiated y tendran un costo diferente.
