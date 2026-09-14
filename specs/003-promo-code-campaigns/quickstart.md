# Quickstart: Distribución de Códigos Promocionales

**Feature**: `003-promo-code-campaigns` | **Date**: 2026-09-13

Guía paso a paso para configurar, ejecutar y verificar el módulo de códigos promocionales y su integración con campañas de marketing en el entorno de desarrollo local.

---

## 1. Requisitos Previos y Configuración

1. Asegúrate de que las migraciones de base de datos estén aplicadas:
   ```powershell
   php artisan migrate
   ```

2. Verifica la configuración de correo y colas en `.env`:
   ```dotenv
   MAIL_MAILER=log
   QUEUE_CONNECTION=database
   ```

3. Inicia el worker de colas en una terminal independiente para procesar los despachos:
   ```powershell
   php artisan queue:listen --queue=default
   ```

---

## 2. Escenario de Validación 1: Importación de Códigos Promocionales (CSV)

1. Accede al panel administrativo en `/admin`.
2. En el menú de navegación, navega al grupo **Marketing** ➔ **Códigos Promocionales**.
3. Haz clic en el botón superior **"Importar Códigos (CSV)"**.
4. Sube un archivo CSV con códigos de prueba de Google Play, por ejemplo:
   ```csv
   Promotion code
   4DUJQ6ASZ392Z0EARXBLL64
   1V9L9TGAP3FZT5RRFCQXW8L
   HCMNAPQA2XW42ZQC44GWYZX
   ```
5. Pulsa **Confirmar Importación**:
   - **Resultado esperado**: Notificación informando de 3 códigos importados.
   - En la tabla de códigos, los 3 registros aparecen con estado *"Disponible"* (`assigned_email` vacío).

---

## 3. Escenario de Validación 2: Redacción de Campaña y Validación Preventiva

1. Ve a **Marketing** ➔ **Campañas de Marketing** ➔ **Nueva Campaña**.
2. Completa los campos:
   - **Asunto**: *"¡Consigue ScoreBox PRO gratis con este código, {{name}}!"*
   - **Tipo de campaña**: Selecciona *"Código Promocional (Play Store)"*.
   - Observa cómo aparece el indicador de stock: *"3 códigos disponibles en inventario"*.
   - Comprueba que la casilla *"Excluir usuarios que ya hayan recibido un código promocional"* está marcada por defecto.
   - **Contenido**:
     ```html
     <p>Hola {{name}},</p>
     <p>Usa este código para activar tu versión PRO sin publicidad:</p>
     <p><strong>{{promotioncode}}</strong></p>
     <p><a href="https://play.google.com/redeem?code={{promotioncode}}">Canjear directamente en Google Play</a></p>
     ```
3. Guarda el borrador.

---

## 4. Escenario de Validación 3: Envío de Prueba (Modo Test)

1. En la lista de campañas, localiza la campaña creada y pulsa **"Enviar Prueba"**.
2. El modal confirma que se enviará a las direcciones de prueba del sistema.
3. Confirma el envío y revisa el log de Laravel (`storage/logs/laravel.log`):
   - **Resultado esperado**: El correo en el log contiene un código simulado como `PROMO-TEST-XXXXXXXX`.
   - Vuelve a **Códigos Promocionales**: Comprueba que los 3 códigos reales siguen estando disponibles (cero códigos consumidos).

---

## 5. Escenario de Validación 4: Envío Real y Asignación Atómica

1. Pulsa la acción **"Pasar a real"** en la campaña y selecciona la audiencia (ej. *Solo usuarios Free*).
2. Pulsa **"Enviar Campaña"**:
   - Si los destinatarios son <= 3: El modal permite confirmar el envío.
   - Si los destinatarios fueran > 3: El modal bloquea la confirmación advirtiendo del déficit.
3. Tras confirmar el envío:
   - La campaña transiciona a `queued` y luego a `sent`.
   - En **Códigos Promocionales**, los códigos usados pasan a estado *"Asignado"*, mostrando el email y el UID del usuario destinatario.
   - En el log de correo, cada destinatario recibió su código alfanumérico exclusivo.

---

## 6. Escenario de Validación 5: Acción Manual de "Liberar Código"

1. En la tabla de **Códigos Promocionales**, localiza un código asignado.
2. En la columna de acciones del registro, pulsa **"Liberar código"**.
3. Tras confirmar el diálogo, el código vuelve a quedar libre (`assigned_email` y `assigned_uid` en blanco) disponible para futuras campañas.

---

## 7. Ejecución de la Suite de Pruebas Automatizadas

```powershell
php artisan test --filter=PromotionalCode
```

