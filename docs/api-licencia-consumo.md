# Guia de consumo API de licencia

Esta guia define como integrar otro sistema con la API de licencia de Billing Manager.

## 1) Objetivo

Permitir que un sistema externo valide en tiempo real si una licencia puede usar el producto.

## 2) Endpoint oficial

- Metodo: GET
- Ruta: /api/v1/license/status
- URL ejemplo local: http://localhost:8000/api/v1/license/status

## 3) Autenticacion requerida

Enviar ambos headers en cada request:

- X-License-Code: codigo de licencia (ejemplo LIC-XXXXXXXXXXXX)
- X-License-Secret: secreto asociado a la licencia

Header recomendado adicional:

- Accept: application/json

## 4) Ejemplo de request

curl -X GET "http://localhost:8000/api/v1/license/status" \
  -H "Accept: application/json" \
  -H "X-License-Code: LIC-TT3VQBA1B5RU" \
  -H "X-License-Secret: TU_SECRETO"

## 5) Contrato de respuesta exitosa (200)

Campos principales:

- ok: boolean
- license_code: string
- status: string
- can_access: boolean
- reason_code: string
- days_remaining: number|null (dias restantes para el limite vigente)
- expires_on: date|null (fecha de vencimiento aplicable)
- checked_at: datetime ISO8601
- service.name: string|null
- subscription.id: number
- subscription.name: string
- subscription.billing_cycle: monthly|yearly
- subscription.amount: string decimal
- subscription.currency: string
- subscription.is_active: boolean
- subscription.has_trial: boolean
- subscription.trial_ends_at: date|null
- subscription.trial_days_remaining: number|null
- subscription.next_renewal_at: date|null
- subscription.renewal_days_remaining: number|null
- coverage.last_covered_period: YYYY-MM|null

Nota de negocio:

- Si hay periodo de prueba y no se define una renovacion manual, la renovacion se fija en la misma fecha de fin de prueba (sin dias extra de gracia).

## 6) Estados funcionales

- active: licencia habilitada, acceso permitido
- trial_active: en prueba, acceso permitido
- overdue: vencida, acceso denegado
- suspended: suscripcion inactiva, acceso denegado

Semantica de days_remaining:

- trial_active: dias restantes hasta trial_ends_at
- active: dias restantes hasta next_renewal_at (o null si no hay limite)
- overdue/suspended: 0

Regla de consumo recomendada:

- Permitir uso solo cuando can_access = true
- Mostrar status y reason_code para diagnostico

## 7) Errores esperados

- 401 invalid_credentials: secreto incorrecto
- 403 revoked: licencia revocada
- 404 not_found: licencia no existe o API deshabilitada
- 422 invalid_request: faltan headers requeridos
- 429 too_many_requests: limite de peticiones

## 8) Recomendaciones de implementacion en el sistema consumidor

- Configurar timeout corto (2 a 5 segundos)
- Reintentar solo en errores transitorios (timeout, 5xx)
- No reintentar en 401/403/404/422
- Cachear resultado por 30 a 120 segundos para reducir trafico
- Registrar ultimo status recibido para soporte
- Nunca loggear X-License-Secret en texto plano

## 9) Variables sugeridas en el sistema consumidor

- LICENSE_API_BASE_URL
- LICENSE_API_CODE
- LICENSE_API_SECRET
- LICENSE_API_TIMEOUT_MS
- LICENSE_API_CACHE_SECONDS

## 10) Checklist de QA para integracion

- Caso valido devuelve 200 y can_access true
- Secreto invalido devuelve 401
- Licencia revocada devuelve 403
- API deshabilitada devuelve 404
- Falta de headers devuelve 422
- Manejo correcto de 429 con backoff

## 11) Versionado y cambios

- Version actual: v1
- Cualquier cambio breaking debe publicarse en una nueva version de ruta (ejemplo /api/v2/...)
- Mantener v1 estable para integraciones existentes
