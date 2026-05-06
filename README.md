# Billing Manager

Sistema interno para control de suscripciones, cobros, costos y rentabilidad por servicio.

Estado actual: base inicial del repositorio.

## Objetivo

Construir una aplicacion interna, simple y funcional, enfocada en:
- Inventario de sistemas/servicios
- Gestion de suscripciones y renovaciones
- Registro de cobros
- Registro de costos directos y compartidos
- Prorrateo de costos compartidos por servicio
- Vista financiera con margen por servicio

## Stack Tecnologico

- Aplicacion base: Laravel full-stack (PHP)
- Render de UI: Blade
- Interactividad: Livewire + Alpine.js
- Estilos: Tailwind CSS
- Base de datos: MySQL (compatible con hosting compartido en Hostinger)

## Enfoque de arquitectura

- Una sola instancia para uso interno
- Sin multi-tenant en esta primera etapa
- Monolito modular para reducir complejidad operacional
- API de integracion como capa futura (sin separar frontend/backend al inicio)

## Estructura esperada (proxima fase)

- app/: dominio y logica de negocio
- resources/views/: vistas Blade
- routes/web.php: flujo principal de UI interna
- routes/api.php: endpoints de integracion futuros
- docs/: decisiones de arquitectura, modelo de datos y roadmap

## Roadmap inicial

1. Inicializar aplicacion Laravel monolitica
2. Definir modelo de datos base (servicios, suscripciones, cobros, costos)
3. Construir dashboard interno con Blade + Livewire
4. Agregar prorrateo y reportes financieros
5. Exponer API por sistema/usuario como fase posterior

## Seguridad y buenas practicas

- Variables sensibles fuera del repositorio
- No versionar llaves, secretos, dumps ni logs
- Configurar backups periodicos en infraestructura

## Requisitos para desarrollo local (cuando se inicialice Laravel)

- PHP 8.2+
- Composer 2+
- MySQL 8+
- Node.js 20+ (recomendado para compilar assets de Tailwind con Vite)

## Licencia

Uso privado/interno.
