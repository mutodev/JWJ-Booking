# Requerimientos pendientes — JamWithJamie

**Fecha de análisis:** 2026-08-28
**Fuente:** `C:\Users\caice\Downloads\Notas para App.pdf`
**Método:** se cruzó cada punto del PDF contra los commits de esta semana (24–28 ago) y contra el código actual (`master` == `origin/master`). Nada aquí es inventado: cada diagnóstico apunta al archivo y línea donde está el problema o donde habría que trabajar.

---

## Lo que YA se resolvió esta semana (referencia, no hacer nada)

| Punto del PDF | Commit(s) |
|---|---|
| START TIME: la hora de prevista salía del evento y no del servicio | `11aeeb9`, `78895f2`, `f022b35` |
| No salía el tipo de evento en el ADMIN | `d3e1ac9` |
| Descuentos aplicando a todos los servicios extra (debe ser solo base) | `4169b7a` |
| Saludo con solo primer nombre en plantillas de email | `82c75de` |
| Filtrar reservas por status / pago (multi-select) | `34d414d` |
| Sección de notas en el formulario inicial ("Any other details or special requests") | `5746755` |
| Ver más reservas por página (20/100) + paginación | `6ba9088`, `ebc3e6e` |
| Poder eliminar carritos abandonados + filtro N/A | `1f3fc66` |
| Email "recibimos tu reserva" no incluía los add-ons | `c1e965f` |

---

# PARTE A — Correcciones pendientes (bugs, sin cotizar)

## A1. Número de niños y edades del segundo formulario salen mal

**Texto original (PDF):**
> "No estoy recibiendo bien el número de niños y edades del segundo formulario, siempre me sale el número 5 o 20, y age range (edades) me sale la cantidad el rango de 1-10 o 11-30 niños. A veces el rango me sale 1-10 niños y el valor es de un duo (11-35 niños) por lo que no corresponde a lo que los clientes están eligiendo en el formulario."
> "Revisar niños vs opción elegida"

### Diagnóstico (confirmado en código)

Hay **tres bugs distintos** aquí:

**1. El "número de niños" siempre es 5 o 20 → porque son valores hardcodeados.**
El formulario público (`frontend/src/components/home/form/Step1.vue:105-109`) solo ofrece 3 radios de rango: `"1-10 kids"`, `"11-30 kids"`, `"31+ kids"`. No pide un número exacto (salvo 31+, que va a inquiry).
El backend convierte ese rango a un número inventado con el punto medio:
- `app/Services/ReservationService.php:445-449` (path `createFromForm`) → `"11-30 kids"` = 20, `"1-10 kids"` = 5
- Mismo hardcode en el frontend: `frontend/src/components/home/form/Step4.vue:307-311` y `366-377`

Ese `$selectedKids` se guarda tal cual en `children_count` (`ReservationService.php:568`).

**2. El campo "Age Range" (edades) muestra el rango de CANTIDAD, no de edad.**
En `frontend/src/components/home/Home.vue:413-414` se arma el objeto `information`:
```js
childAge: null, // No se recolecta sin Step5
ageRange: customer?.childrenRange || null,   // <-- se copia el rango de CANTIDAD
```
Luego el backend guarda ese valor en `children_age_range`:
- `ReservationService.php:591` → `'children_age_range' => $information['ageRange'] ?? null`
- (además `children_age_range` se asigna **dos veces** en el mismo array literal: línea 569 y línea 591 — la segunda gana. Código a limpiar.)

El formulario inicial **nunca pide la edad de los niños**. La edad real solo se captura después del pago, en `setConfirmation.vue` / `ConfirmationUpdate.vue` (campos `ageRange` y `childAge`). Por eso en el admin (`ReservationView.vue:85`, `DetailField label="Age Range"`) aparece "1-10 kids" en vez de edades.

**3. "El rango me sale 1-10 niños y el valor es de un duo (11-35 niños)".**
`Step2.vue:134-136` solo filtra servicios de 1 performer cuando el rango NO es `"1-10 kids"`, pero no impide que un cliente que eligió `"1-10 kids"` termine comprando un precio cuya escala es de 11-35 niños. Queda `children_count`/`children_age_range` en desacuerdo con el `service_price_id` elegido.

### Qué hay que hacer

1. **Decisión de producto (Jamie):** ¿el cliente debe teclear el número exacto de niños en el formulario inicial, o basta con guardar el rango tal cual eligió? Recomendado: agregar un input numérico "Exact number of children" (como ya existe para 31+) y guardar ese número en `children_count`; si no, dejar de inventar el punto medio y guardar el string del rango.
2. **Separar los dos conceptos**: `children_count` (cantidad) y `children_age_range` (edades). Dejar de copiar `childrenRange` dentro de `ageRange` en `Home.vue:414`. Si el formulario inicial no pide edades, guardar `children_age_range = null` y que el admin lo vea como "Pending (captured after payment)" en vez de un rango de cantidad.
3. **Quitar la doble asignación** de `children_age_range` en `ReservationService.php:560-597`.
4. **Validar coherencia niños ↔ precio elegido**: al elegir el `service_price`, si su escala de niños no cubre el `childrenRange` seleccionado, avisar/bloquear en `Step2.vue`.
5. Revisar también el path admin (`ReservationService::create`, líneas ~178-184 y 228/250) que usa `form.extraChildren` y `form.childrenAgeRange` — ahí sí llega un valor real; confirmar que el label del form admin dice lo correcto.

**Archivos:** `frontend/src/components/home/form/Step1.vue`, `Step2.vue`, `Step4.vue`, `frontend/src/components/home/Home.vue`, `app/Services/ReservationService.php`, `frontend/src/components/admin/reservations/ReservationView.vue`.
**Migración:** ninguna nueva (las columnas `children_count` y `children_age_range` ya existen).
**Dificultad:** media. El bug 2 (dejar de copiar el rango de cantidad como edad) es un cambio de 1 línea + limpieza; los bugs 1 y 3 requieren decisión de producto y tocar el formulario.

---

## A2. Editar plantillas de email sin que se reseteen todas

**Texto original (PDF):**
> "Editar plantillas de emails sin que se resetean todas las plantillas"

### Diagnóstico (confirmado en código)

El panel admin sí permite editar plantillas (`EmailTemplateController::updateData` → `EmailTemplateService::update`, guarda en la tabla `email_templates`). El problema es que **varios seeders sobrescriben `subject`, `body` y `content` incondicionalmente cada vez que se corren**, y esos seeders se ejecutan en cada deploy:

- `app/Database/Seeds/EmailTemplateSeeder.php:183-194`: si ya existe un template con ese `slug`, hace `->update($template)` con TODO el contenido hardcodeado del seeder. Cualquier edición del admin se pierde.
- `app/Database/Seeds/FixEmailTemplateBodiesSeeder.php:172-174, 295-297, 401-403`: `->update(['body' => $body])` sin condición.
- `app/Database/Seeds/PatchEmailTemplatesSeeder.php:132`: `->update(['body' => $newBody])` (este al menos chequea si falta el placeholder antes).
- Otros: `FixEmailPromoDiscountSeeder`, `FixDurationInEmailsSeeder`, `FixEmailTemplateBrandingSeeder`, `FixBrandingCapitalizationSeeder`, `PatchEmailTemplatesSeeder`, `AddAddonsRowToReservationConfirmationSeeder`, etc. — toda la familia `Fix*/Patch*EmailTemplate*` reescribe cuerpos.

### Qué hay que hacer

1. **Marcar plantillas como "editadas por el usuario"**: agregar columna `is_customized` (o `locked_at` / `last_edited_by`) a `email_templates` vía migración. `EmailTemplateService::update()` la pone en `true`.
2. **Hacer los seeders idempotentes y respetuosos**: en `EmailTemplateSeeder` y en todos los `Fix*/Patch*`, saltar el `update` si `is_customized == true` (solo `insert` cuando el slug no existe).
3. **Decidir una política de deploy**: `EmailTemplateSeeder` debería ser "insertar si falta" y nunca "reescribir". Los seeders `Fix*` fueron parches puntuales de fechas pasadas — evaluar archivarlos / no volver a correrlos en el VPS (documentar en `project-pending-deploy` de la memoria qué seeders son seguros).
4. Como red de seguridad: guardar un backup de `email_templates` antes de cada `db:seed` en el VPS.

**Archivos:** nueva migración `AlterEmailTemplatesAddIsCustomized`, `app/Services/EmailTemplateService.php`, `app/Database/Seeds/EmailTemplateSeeder.php` y toda la familia `app/Database/Seeds/*EmailTemplate*` / `Fix*Email*` / `Patch*`.
**Migración:** SÍ (una, agregar columna).
**Dificultad:** media. Poca lógica, pero hay que tocar ~10 seeders y acordar la política de deploy.

---

## A3. "Sigue con eso"

**Texto original (PDF):** aparece dos veces como bullet suelto ("Sigue con eso" / "Sigue con eso") debajo de "No sale el tipo de evento" y de "Revisar niños vs opción elegida".

### Diagnóstico

Nota ambigua de Jamie. Por posición en el PDF parece significar "esto sigue pasando / seguir con este tema" referido al punto anterior (tipo de evento en ADMIN, ya resuelto en `d3e1ac9`; y niños vs opción, ver A1).

### Qué hay que hacer

Preguntar a Jamie a qué se refiere exactamente antes de estimar. Muy probablemente ya cubierto por `d3e1ac9` (tipo de evento) y A1 (niños). 0 código hasta confirmar.

---

# PARTE B — Nuevas actualizaciones (pendiente a cotizar con Jamie)

> Estos puntos son de la sección "Nuevas actualizaciones, pendiente a cotizar" del PDF. Requieren acordar precio/alcance con Jamie **antes** de implementar.

## B1. Investigar: clientes que no reciben el email después del pago

**Texto original (PDF):**
> "Ya van dos clientes que indican que no recibieron el email después del pago* preguntar a Cristian — siyaamunjal@gmail.com  celine.klepach@gmail.com"

### Diagnóstico (parcial, confirmado en código)

El email de "pago confirmado" se manda en `ReservationService::sendPaymentConfirmationEmail()` (`ReservationService.php:1557`), disparado desde `handlePaymentCompleted()` (`:1468`). `handlePaymentCompleted` se llama por dos vías:
- Webhook de Stripe `checkout.session.completed` → `ReservationController::stripeWebhook()` (`:314-321`)
- Verificación post-redirect → `ReservationController::verifyPayment()` → `ReservationService::verifyPayment()` (`:1481`)

Puntos frágiles detectados:
1. **Si el email falla, se traga la excepción**: `ReservationService.php:1604-1606` hace `catch` y solo `log_message('error', ...)`. No se registra en ningún lado visible para Jamie, no se reintenta.
2. **No se registra en el historial de emails**: `sendPaymentConfirmationEmail` NO llama a `recordEmailHistory()` (a diferencia de `sendTemplateEmail`, `:1188`). No hay forma de saber desde el admin si el email salió.
3. **El webhook depende de config correcta** (`stripe.webhookSecret` en `.env` del VPS, endpoint `POST /stripe/webhook` registrado en el dashboard de Stripe). Si el webhook no llega, el email solo se manda si el cliente vuelve a la página de éxito (`verifyPayment`).
4. **Brevo**: `BrevoEmailService.php` usa `getenv('brevo.apiKey')`; con `'verify' => false` en Guzzle. Si la API key está mal o el sender no está validado en Brevo, falla silenciosamente.

### Qué hay que hacer

1. Hablar con Cristian (infra/Stripe/Brevo) como pide la nota: confirmar que el webhook de Stripe está configurado y llegando en producción (revisar logs de Stripe dashboard → Webhooks → intentos fallidos).
2. Revisar `writable/logs/` del VPS buscando `"Failed to send payment confirmation email"` para esos dos clientes.
3. Verificar en Brevo el log de envíos transaccionales para `siyaamunjal@gmail.com` y `celine.klepach@gmail.com` (¿bounce? ¿spam? ¿nunca se intentó?).
4. **Mejora de código** (esto sí se puede cotizar/hacer): registrar `sendPaymentConfirmationEmail` en `reservation_email_history` con `status = 'Sent' | 'Failed'`, y mostrarlo en el admin. Así el problema deja de ser invisible.
5. Considerar un reintento / cola para emails fallidos.

**Archivos:** `app/Services/ReservationService.php` (métodos `sendPaymentConfirmationEmail`, `handlePaymentCompleted`), `app/Controllers/ReservationController.php` (`stripeWebhook`), config `.env` del VPS, dashboards de Stripe y Brevo.
**Migración:** ninguna (la tabla `reservation_email_history` ya existe).
**Dificultad:** impredecible. La causa raíz puede ser config de terceros (rápido) o un fallo del webhook en producción (más profundo). La mejora de logging/visibilidad es media.

---

## B2. Agregar opción de CC en los emails desde la plataforma

**Texto original (PDF):**
> "Agregar opción de CC emails desde la plataforma"

### Diagnóstico (confirmado en código)

Hoy **no existe CC en ninguna parte**:
- `BrevoEmailService::sendEmail($to, $subject, $htmlContent)` (`BrevoEmailService.php:34-44`) solo arma `'to' => [['email' => $to]]`. El SDK de Brevo (`SendSmtpEmail`) soporta `cc` y `bcc` pero no se usan.
- La tabla `email_templates` no tiene columna de CC.
- El envío manual de email a una reserva (`ReservationService::sendTemplateEmail`, `:1141`) no acepta destinatarios extra.

### Qué hay que hacer

1. Extender `BrevoEmailService::sendEmail()` para aceptar `array $cc = []` (y opcionalmente `$bcc`) y pasarlo al `SendSmtpEmail`.
2. **Decisión de producto:** ¿CC fijo global (ej. siempre copiar a `operations@jamwithjamie...`) o CC editable por envío en el compositor del admin? Recomendado: ambos — un CC por defecto configurable + campo editable en el modal de "Send email".
3. Si es CC por defecto: nueva config (env var o tabla de settings). Si es por envío: agregar campo en el frontend del compositor de emails (`send-template-email`) y en `ReservationController::sendTemplateEmail` / `ReservationService::sendTemplateEmail`.
4. Guardar los CC usados en `reservation_email_history` (agregar campo o serializar en `recipient_email`).

**Archivos:** `app/Services/BrevoEmailService.php`, `app/Services/ReservationService.php`, `app/Controllers/ReservationController.php`, `app/Controllers/EmailTemplateController.php` (`:94`), frontend del compositor de emails, posible migración para `cc` en `reservation_email_history`.
**Migración:** opcional (1, si se guarda el CC en el historial).
**Dificultad:** media-baja.

---

## B3. Registrar en el historial de email el envío del link de pago y el pago recibido

**Texto original (PDF):**
> "Agregar cuando se envía email link de pago + pago recibido en historial de email"

### Diagnóstico (confirmado en código)

El historial de emails **ya existe** parcialmente:
- Tabla `reservation_email_history` (migración `2026-07-06-010000_CreateReservationEmailHistoryTable.php`), modelo `ReservationEmailHistoryModel`.
- Endpoint `GET /reservations/{id}/email-history` → `ReservationController::getEmailHistory` → `ReservationService::getEmailHistory` (`:1210`).
- Se llena **solo** desde `ReservationService::sendTemplateEmail()` vía `recordEmailHistory()` (`:1188`) — es decir, únicamente cuando el admin manda un email manual desde el compositor.

**NO se registra:**
- El email de link de pago: `ReservationService::sendPaymentEmail()` (`:1063`) manda con `emailService->sendEmail(...)` en `:1124` pero nunca llama a `recordEmailHistory()`.
- El email de confirmación de pago recibido: `sendPaymentConfirmationEmail()` (`:1557`, envío en `:1605`) — tampoco.
- El email de "reserva recibida": `sendConfirmationEmail()` (`:1611`, envío en `:1651`) — tampoco.
- Recordatorio semanal: `sendWeekReminders()` (`:1683`, envío en `:1701`) — tampoco.

### Qué hay que hacer

1. Llamar `recordEmailHistory([...])` en cada uno de esos métodos, con `template_name` descriptivo ("Payment Link Sent", "Payment Received", "Reservation Received", "Week Reminder"), `status` según resultado del `try/catch`, y `sent_by = 'system'` cuando es automático.
2. Extender el evento "pago recibido": además del email, registrar en el historial una fila tipo evento ("Payment received — $X via Stripe") aunque no sea un email, o crear una tabla de timeline. **Decisión de producto:** ¿el historial es solo de emails o un timeline de la reserva? La nota de Jamie sugiere que quiere ver "pago recibido" ahí, así que probablemente un timeline.
3. Mostrar estos eventos en el frontend del admin (la vista de email-history ya existe; agregar los nuevos tipos).
4. Considerar backfill: las reservas viejas no tendrán estas filas.

**Archivos:** `app/Services/ReservationService.php` (métodos `sendPaymentEmail`, `sendPaymentConfirmationEmail`, `sendConfirmationEmail`, `sendWeekReminders`, `handlePaymentCompleted`), frontend de la vista de historial de emails en la reserva.
**Migración:** posiblemente 1 (si el historial pasa a ser timeline con un campo `event_type`, o si se relaja `template_id` a nullable — hoy `recordEmailHistory` recibe `template_id`).
**Dificultad:** media. El registro de emails es mecánico; el "pago recibido" como evento requiere decidir el modelo.

---

## B4. Automático en Brevo: emails de carritos abandonados semanal (7 días)

**Texto original (PDF):**
> "Agregar automático en Brevo emails de carritos abandonados - por semana (7 días)"

### Diagnóstico (confirmado en código)

Hoy el follow-up de carrito abandonado es **100% manual**:
- `ReservationDraftService::sendFollowUpEmail($id)` (`:186`) — manda 1 email para 1 draft, usando el template `abandoned_cart_followup`, y marca `follow_up_sent_at`.
- Se dispara solo desde el admin: `POST /reservation-drafts/{id}/follow-up` → `ReservationDraftController::sendFollowUp` (`:122`).
- `getAbandoned($hoursOld = 24)` (`:150`) ya sabe listar drafts abandonados por antigüedad.

**Ya existe la infraestructura para automatizarlo** (patrón a copiar): el recordatorio semanal de reservas está hecho con un CLI command + cron:
- `app/Commands/SendWeekReminders.php` (`php spark reminders:week`)
- `ReservationService::sendWeekReminders()` (`:1683`) itera y marca `week_reminder_sent = 1` para no duplicar.
- Seeder del template: `WeekReminderEmailSeeder`. Para abandonados ya hay `AbandonedCartFollowUpEmailSeeder`.

### Qué hay que hacer

1. Crear `app/Commands/SendAbandonedCartFollowUps.php` (ej. `php spark carts:followup`), espejo de `SendWeekReminders`.
2. Crear `ReservationDraftService::sendAbandonedFollowUps()`: buscar drafts con `completed = 0`, `email` no vacío, `last_activity_at` hace ≥ 7 días, y `follow_up_sent_at` NULL (para no reenviar). Iterar, mandar, marcar `follow_up_sent_at`.
3. Registrar un cron en el VPS (crontab) que corra el comando 1×/día (el filtro de 7 días + `follow_up_sent_at` evita spam).
4. **Decisión de producto (Jamie dice "en Brevo"):** ¿quiere que el disparo sea un cron nuestro que manda vía la API transaccional de Brevo (como el week reminder), o quiere subir los contactos abandonados a una lista/automation de Brevo y que Brevo maneje la secuencia? Lo primero es consistente con lo que ya existe; lo segundo es más trabajo (integrar `BrevoContactService` + automations) pero le da a Jamie control desde Brevo.
5. Confirmar la definición de "abandonado": ¿7 días sin actividad? ¿solo si nunca completó? ¿un solo follow-up o una secuencia?

**Archivos:** nuevo `app/Commands/SendAbandonedCartFollowUps.php`, `app/Services/ReservationDraftService.php`, `app/Models/ReservationDraftModel.php` (query `getAbandoned` con ventana de 7 días), crontab del VPS. Si es vía lista de Brevo: `app/Services/BrevoContactService.php`.
**Migración:** ninguna (`follow_up_sent_at` ya existe en `reservation_drafts`).
**Dificultad:** media si se copia el patrón de `SendWeekReminders`; alta si Jamie quiere automations nativas de Brevo.

---

## B5. Crear reservas / links de pago personalizados (monto libre + descripción)

**Texto original (PDF):**
> "Opción de crear reservas o links de pago personalizados, con el valor que queramos poner y agregar la descripción acorde (ejemplo late fees o eventos personalizados)"

### Diagnóstico (confirmado en código)

Hoy **el monto a cobrar siempre se calcula** a partir de servicio + add-ons + fees + descuento. No hay forma de cobrar un monto arbitrario:
- `StripeService::createCheckoutSession(float $amount, ...)` (`StripeService.php:33`) sí acepta un `$amount` y un `$description` libres — la capa de Stripe ya lo soporta.
- Pero quien lo llama (`ReservationService`, alrededor de `:1418-1422`) siempre pasa `$reservation->total_amount` y una descripción fija `'Event Reservation - ' . service_name`.
- La creación de reserva (`ReservationService::create` y `createFromForm`) exige `service`, `price`, `zipcode`, etc. No se puede crear una "reserva" que sea solo "Late fee $75".
- Existe `$reservation->description` (se usa en el email de pago, `:1076`) pero no se puede setear libremente desde el admin al crear.
- Ruta existente reutilizable: `POST /reservations/{id}/regenerate-payment` → `regeneratePayment`.

### Qué hay que hacer

1. **Decisión de producto:** ¿es (a) un "payment link suelto" no atado a una reserva completa, o (b) una reserva especial con line items manuales? La nota menciona ambos ("reservas o links de pago").
2. Para links de pago sueltos: nueva entidad ligera (`custom_payment_links` o reservas con `type = 'custom'`) con: `amount`, `description`, `customer_email`, `status`, `stripe_session_id`, `paid_at`. Endpoint admin para crearlo, que llama a `StripeService::createCheckoutSession($amount, $email, $id, $description)` (ya sirve tal cual).
3. UI admin: formulario "New payment link" con monto, descripción, email del cliente; genera y muestra la URL para copiar/enviar.
4. Webhook: `stripeWebhook` (`ReservationController.php:314`) debe reconocer estos pagos por `metadata` y marcarlos pagados sin pasar por `handlePaymentCompleted` (que asume reserva completa).
5. Email: plantilla nueva para link de pago custom, o reusar `payment_notification` con la descripción libre.
6. Que aparezcan en la tabla de reservas/pagos con su status y en el historial.

**Archivos:** nueva migración (`custom_payment_links` o columnas en `reservations`), nuevo controller/service o extensión de `ReservationController`/`ReservationService`, `app/Services/StripeService.php` (mínimo, ya soporta), frontend admin (nueva pantalla), plantilla de email.
**Migración:** SÍ (1 tabla nueva o varias columnas).
**Dificultad:** alta. Feature nueva de punta a punta (UI + Stripe + webhook + emails + listado).

---

## B6. Modificar servicios / agregar add-ons con la reserva ya creada

**Texto original (PDF):**
> "Opción de modificar los servicios (dos situaciones, etc.) - o agregar add-ons una vez la reserva esté creada"

### Diagnóstico (confirmado en código)

- Existe `ReservationEdit.vue` en el admin y rutas CRUD de `reservation-addons` (`Routes.php:198-206`: `POST/PUT/DELETE /reservation-addons/...`), modelo `ReservationAddonModel`, servicio `ReservationAddonService`.
- **Pero** los totales de la reserva (`base_price`, `addons_total`, `extra_children_fee`, `travel_fee`, `expedite_fee`, `discount_amount`, `total_amount`, `duration_hours`) se calculan **una sola vez** al crear (`ReservationService::create` / `createFromForm`, líneas ~500-597). No hay un método que **recalcule** el total de una reserva existente cuando cambian sus add-ons o su servicio.
- Si la reserva ya está pagada (`is_paid = true`), no hay flujo para cobrar la diferencia (o reembolsar). `updateGratuity` (`:1660`) directamente bloquea cambios si `is_paid`.
- El email de confirmación arma los add-ons con `buildAddonsRow($reservation->id)` (`:1647`) leyendo la tabla, así que si se agregan después, el email viejo no se reenvía solo.

### Qué hay que hacer

1. Crear `ReservationService::recalculateTotals(string $reservationId)`: relee servicio + add-ons + zipcode + promo y recompone todos los campos de precio y `duration_hours` con la misma lógica centralizada de `create()`. Refactor: extraer esa lógica hoy inline a un método reutilizable.
2. Hacer que `ReservationAddonService` (create/update/delete) dispare ese recálculo.
3. Permitir cambiar el `service_price_id` desde `ReservationEdit.vue` y recalcular.
4. **Decisión de producto:** ¿qué pasa si la reserva ya está pagada? Opciones: (a) generar un link de pago por la diferencia (se apoya en B5), (b) solo permitir cambios antes del pago, (c) registrar la diferencia como saldo pendiente.
5. Reenviar / registrar un email de "reserva actualizada" con el nuevo desglose.
6. Registrar el cambio en el historial (se apoya en B3).

**Archivos:** `app/Services/ReservationService.php` (refactor grande de la lógica de cálculo), `app/Services/ReservationAddonService.php`, `app/Controllers/ReservationAddonController.php`, `frontend/src/components/admin/reservations/ReservationEdit.vue`, plantilla de email nueva.
**Migración:** posiblemente (campo de saldo pendiente / ajustes).
**Dificultad:** la más alta de la lista. Toca el core del cálculo de precios, interactúa con pagos ya hechos, y necesita B5 y B3 para estar completa.

---

# Resumen ordenado (más fácil → más difícil)

| Orden | Item | Tipo | Migración | Dificultad |
|---|---|---|---|---|
| 1 | A3 — "Sigue con eso" (aclarar con Jamie) | Aclaración | No | Trivial |
| 2 | A1 — Niños y edades del formulario | Bug | No | Media |
| 3 | A2 — Plantillas de email se resetean | Bug | Sí (1 col) | Media |
| 4 | B2 — CC en emails | Feature (cotizar) | Opcional | Media-baja |
| 5 | B3 — Historial: link de pago + pago recibido | Feature (cotizar) | Posible (1) | Media |
| 6 | B4 — Carritos abandonados automático semanal | Feature (cotizar) | No | Media / Alta |
| 7 | B1 — Email post-pago no llega | Bug (investigación) | No | Impredecible |
| 8 | B5 — Links de pago personalizados | Feature (cotizar) | Sí | Alta |
| 9 | B6 — Modificar servicios / add-ons post-reserva | Feature (cotizar) | Posible | Alta |

**Notas de negocio:**
- A1, A2, A3 son correcciones — no necesitan cotización.
- B1–B6 son de "Nuevas actualizaciones, pendiente a cotizar" — acordar precio/alcance con Jamie antes de arrancar.
- Varios ítems se apoyan entre sí: B6 necesita B5 (cobrar diferencias) y B3 (registrar cambios); B1 se beneficia de B3 (visibilidad de emails).
