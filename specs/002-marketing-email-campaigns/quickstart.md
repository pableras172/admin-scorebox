# Quickstart: Módulo de Campañas de Email Marketing

**Feature**: `002-marketing-email-campaigns` | **Date**: 2026-09-13

Guía rápida para configurar, ejecutar y validar el módulo de marketing en desarrollo local.

---

## 1. Configuración de Entorno Local

En tu archivo `.env` de `admin/`:

```dotenv
# Driver de correo para desarrollo local (escribe en storage/logs/laravel.log)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="no-reply@scorebox.app"
MAIL_FROM_NAME="ScoreBox"

# Driver de cola para procesamiento asíncrono
QUEUE_CONNECTION=database
```

Asegúrate de ejecutar las migraciones locales:
```powershell
php artisan migrate
```

---

## 2. Iniciar el Worker de Colas

Para que los envíos de campañas se procesen en segundo plano:
```powershell
php artisan queue:listen --queue=default
```

---

## 3. Flujo de Prueba en el Panel Administrativo (Filament)

1. Accede al panel administrativo (`/admin`).
2. En el menú lateral, pulsa en el nuevo grupo **Marketing** ➔ **Campañas de Marketing**.
3. Pulsa **Nueva Campaña**:
   - **Asunto**: *"¡Novedades en ScoreBox para {{name}}! 🎼"*
   - **Audiencia**: Selecciona *"Solo usuarios Free"*.
   - **Contenido**: Redacta el mensaje en el editor enriquecido.
4. Guarda el borrador.
5. En la lista de campañas o en la vista de detalle, pulsa la acción **"Enviar Campaña"**:
   - Observa cómo se despliega el modal interactivo calculando el número de destinatarios netos disponibles.
   - Confirma el envío.
6. La campaña pasará a estado `queued` y luego `sent`.
7. En el log de Laravel (`storage/logs/laravel.log`) podrás revisar el correo generado con su cabecera `List-Unsubscribe` y su enlace de baja firmado.

---

## 4. Validación de la Desuscripción (Opt-out)

1. Copia la URL de desuscripción firmada presente en el pie del correo registrado en el log.
2. Ábrela en tu navegador:
   - Verás la pantalla de confirmación: *"Te has dado de baja correctamente"*.
3. En el panel de Filament, ve a **Marketing** ➔ **Bajas de Marketing**:
   - Comprueba que la dirección de correo aparece listada en la tabla con la fecha exacta.
4. Si vuelves a intentar enviar una campaña a ese segmento, el sistema excluirá automáticamente a ese usuario.
5. Pulsa en la pantalla web el botón *"Volver a suscribirme"*:
   - Comprueba que el correo se elimina de la tabla de bajas y vuelve a quedar activo.

---

## 5. Ejecución de Pruebas Automatizadas

```powershell
php artisan test --filter=Marketing
```

