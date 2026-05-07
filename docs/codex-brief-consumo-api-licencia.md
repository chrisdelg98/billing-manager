# Brief para Codex: integrar consumo de API de licencia

Usa este texto como base en el otro sistema para que Codex implemente la integracion de forma correcta.

## Objetivo

Implementar una capa de validacion de licencia consumiendo Billing Manager en:

GET /api/v1/license/status

con headers:

- X-License-Code
- X-License-Secret

## Requisitos obligatorios

1. Crear cliente HTTP reutilizable para licencia.
2. Leer configuracion desde variables de entorno:
   - LICENSE_API_BASE_URL
   - LICENSE_API_CODE
   - LICENSE_API_SECRET
   - LICENSE_API_TIMEOUT_MS
3. Enviar Accept: application/json en todas las llamadas.
4. Parsear respuesta JSON y mapear:
   - can_access
   - status
   - reason_code
5. Regla de negocio principal:
   - Si can_access = true, permitir operacion.
   - Si can_access = false, bloquear operacion y mostrar mensaje segun status/reason_code.
6. Manejo de errores HTTP:
   - 401 -> credenciales invalidas
   - 403 -> licencia revocada
   - 404 -> licencia no encontrada o API deshabilitada
   - 422 -> request invalido
   - 429 -> throttling (aplicar backoff)
7. No registrar el secreto en logs.
8. Agregar pruebas unitarias y de integracion para todos los casos anteriores.

## Criterios de aceptacion

- El sistema puede validar licencia antes de permitir funcionalidades protegidas.
- Los estados de licencia se reflejan correctamente en UI o logs de negocio.
- La integracion no cae por timeout: maneja retry controlado y fallback.
- El codigo queda desacoplado (servicio o adaptador), no pegado en controladores.

## Flujo recomendado

1. Startup: validar que variables de entorno existen.
2. Request de negocio: llamar servicio de licencia.
3. Si 200:
   - usar can_access para decision.
4. Si error controlado (401/403/404/422):
   - bloquear acceso sin retry.
5. Si error transitorio (timeout/5xx/429):
   - retry con backoff corto.
   - si falla, aplicar politica de degradacion definida por negocio.

## Politica de seguridad

- Secret en variables seguras.
- Enmascarar secreto en logs y trazas.
- Rotar secreto cuando se sospeche exposicion.

## Entregables esperados por Codex

1. Servicio LicenseApiClient.
2. Configuracion por entorno.
3. Mapeo de errores de licencia.
4. Tests de cliente y casos de estado.
5. Documentacion corta de uso interno.
