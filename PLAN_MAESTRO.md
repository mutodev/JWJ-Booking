# PLAN MAESTRO — JamWithJamie

**Autor:** ARCHITECT (ejecución única)
**Fecha:** 2026-08-28
**Fuente de requerimientos:** `REQUERIMIENTOS_PENDIENTES.md` (raíz)
**Base de código analizada:** `master` @ `8709b43` + working tree

Este documento es la **fuente única de verdad** para Developer, Tester y Certifier.
Cada requerimiento se procesa **uno a la vez**, en el ORDEN DE PROCESAMIENTO del final.

---

## 0. Contexto técnico congelado (leer antes de tocar nada)

### 0.1 Stack real

| Capa | Detalle |
|---|---|
| Backend | CodeIgniter 4 (PHP ^8.1), PSR-4 `App\` → `app/` |
| Frontend | Vue 3 + Vite, código en `frontend/src`, build a `public/build/assets` |
| DB | MySQL. Migraciones en `app/Database/Migrations` (naming `YYYY-MM-DD-HHMMSS_Nombre.php`) |
| Pagos | Stripe Checkout Sessions (`app/Services/StripeService.php`) |
| Email | Brevo transaccional (`app/Services/BrevoEmailService.php`) + contactos (`BrevoContactService.php`) |
| Tests | PHPUnit 10.5, `composer test`, suite en `tests/` |

### 0.2 Reglas de arquitectura observadas (respetarlas)

1. **Capas:** `Controller` (parse/HTTP) → `Service` (validación + reglas) → `Repository` (whitelist de campos) → `Model` (allowedFields + UUID en `beforeInsert`).
2. **UUIDs:** todos los PK son `CHAR(36)` generados con `generate_uuid_data()` en `beforeInsert`.
3. **Errores:** los servicios lanzan `CodeIgniter\HTTP\Exceptions\HTTPException` con código HTTP; los controllers mapean `getCode()` a `setStatusCode()` con guarda `>=400 && <600`.
4. **Rutas:** todo bajo `$routes->group('api', ...)` en `app/Config/Routes.php`. Admin con `['filter' => 'verifyToken']`. Las rutas públicas están explícitamente fuera del grupo con filtro.
5. **Doble whitelist:** el Repository filtra con `array_intersect_key(..., array_flip($allowedFields))` y el Model con `$allowedFields`+`protectFields=true`. **Nunca** pasar input crudo del request al Model.

### 0.3 Estado de la suite de tests (CRÍTICO para el Tester)

- `phpunit.xml.dist`: la configuración de base de datos de tests está **comentada** (`database.tests.*`). **NO hay base de datos disponible para los tests.**
- Flags estrictos activos: `failOnRisky="true"`, `failOnWarning="true"`, `beStrictAboutOutputDuringTests="true"`. Un test que emita output o no tenga aserciones **rompe la suite**.
- **Patrón obligatorio para tests nuevos:** sin DB. Dos estrategias ya probadas en el repo:
  - `tests/unit/ReservationServiceChildrenCountTest.php` — sustituye los repositorios por dobles anónimos vía `ReflectionProperty`, y neutraliza `\Config\Database::connect()` inyectando un doble en el cache estático protegido de `CodeIgniter\Database\Config`.
  - `tests/unit/EmailTemplateServiceUpdateTest.php` (working tree, sin commitear) — `createMock()` de PHPUnit + `ReflectionProperty` + `\Config\Services::injectMock('auth', ...)` y `\Config\Services::reset(true)` en `tearDown()`.
- Reutilizar esos dos archivos como plantilla. Nunca escribir un test que dependa de `$this->db` real.

### 0.4 REGLA CRÍTICA de seeders (aplica a TODOS los requerimientos)

> **Nunca modificar un seeder histórico para cambiar datos de producción.**
> Si hace falta sembrar o modificar datos/plantillas, **crear un SEEDER NUEVO independiente**.

Aclaración necesaria para A2 (única excepción, y es aparente, no real): en A2 sí se editan seeders históricos, pero **exclusivamente para agregar una guarda que los vuelve no-destructivos** (saltar filas marcadas como personalizadas). **No se cambia ni un byte del contenido de datos que siembran.** Cualquier cambio de contenido de plantilla debe ir en un seeder nuevo.

`DatabaseSeeder.php` **no** invoca ningún seeder de email — la familia `*EmailTemplate*` / `Fix*` / `Patch*` se corre a mano en el VPS. Ese es exactamente el origen del bug A2.

---

# PARTE A — Correcciones (sin cotizar)

## A1 — Numero de ninos y edades del segundo formulario salen mal

### Estado actual

**IMPLEMENTADO Y COMMITEADO en `8709b43` "Capture exact number of children in public booking form".** No repetir el trabajo.

Lo que ese commit ya resolvio:

| Bug del requerimiento | Resuelto en | Como |
|---|---|---|
| Bug 1 — `children_count` siempre 5 o 20 | `Step1.vue`, `ReservationService::createFromForm()` | Input numerico "Exact number of children" para los rangos `1-10 kids` y `11-30 kids`, con validacion yup condicional y reset al cambiar de rango. Backend resuelve `children_count` desde `customer.exactChildrenCount` con **clamp server-side** a los limites del rango (`$childrenRangeBounds`); si falta, cae al punto medio y loguea `warning`. |
| Bug 2 — "Age Range" mostraba el rango de CANTIDAD | `Home.vue:~414` | `information.ageRange` pasa a `null` (la edad se captura post-pago en `ConfirmationUpdate.vue`). Admin `ReservationView.vue` muestra "Pending (captured after payment)" y el label paso a "Children & Age Range". |
| Bug 3 — rango 1-10 con precio de duo | `Step2.vue` | `filteredServices` filtra a servicios `performers_count > 1` cuando el numero exacto supera 10 (con fallback al rango cuando no hay exacto). |
| Doble asignacion de `children_age_range` | `ReservationService.php` | Eliminada la asignacion duplicada (queda una sola). |
| Labels admin | `ReservationEdit.vue`, `create/ReservationForm.vue` | Placeholder y helper text corregidos. |

Tests ya existentes: `tests/unit/ReservationServiceChildrenCountTest.php` (20 tests, sin DB).

**Que falta:** nada de implementacion. Falta unicamente **verificacion y cierre** (Tester + Certifier).

### Decision de producto tomada (congelada)

Se adopta la recomendacion del requerimiento: **el formulario publico pide el numero exacto de ninos** y ese numero se guarda en `children_count`. `children_age_range` (edades reales) **queda `null` en el flujo publico** y se captura despues del pago. El admin muestra "Pending (captured after payment)" mientras este vacio.

**Fuera de alcance (congelado, NO implementar):**

- Validacion server-side de coherencia entre `children_count` y la escala de ninos del `service_price_id` elegido. El filtro de `Step2.vue` (performers) es la mitigacion aceptada.
- Semantica de `children_count` en el path admin `ReservationService::create()` (lineas ~179-185): cuando llega `form.extraChildren`, `children_count` guarda **ninos extra**, no el total. Es comportamiento preexistente; el commit `8709b43` lo cubrio ajustando los labels del formulario admin. **Se documenta como deuda tecnica conocida y NO se cambia** (cambiarlo alteraria el significado de datos historicos y el calculo de `extra_children_fee`).

### Criterios de aceptacion

1. En el formulario publico, elegir "1-10 kids" muestra el input "Exact number of children" y no permite continuar sin un valor.
2. Un `exactChildrenCount` fuera del rango elegido (ej. 50 con "1-10 kids") se **recorta** server-side al limite del rango; nunca se persiste el valor crudo.
3. Sin `exactChildrenCount` (payload legacy), `children_count` cae al punto medio del rango y se escribe un `log_message('warning', ...)`.
4. `children_age_range` se persiste `null` en el flujo publico; el admin lo renderiza como "Pending (captured after payment)".
5. `children_age_range` aparece **una sola vez** en el array de `createFromForm()`.
6. Con mas de 10 ninos exactos, `Step2.vue` solo lista servicios con `performers_count > 1`.
7. `composer test` pasa en verde, incluido `ReservationServiceChildrenCountTest`.

### Archivos backend a tocar

Sin cambios previstos. Referencia: `app/Services/ReservationService.php` (`createFromForm`, bloque `$childrenRangeBounds`).

### Archivos frontend a tocar

Sin cambios previstos. Referencia: `frontend/src/components/home/form/Step1.vue`, `Step2.vue`, `Step4.vue`, `frontend/src/components/home/Home.vue`, `frontend/src/components/admin/reservations/ReservationView.vue`, `ReservationEdit.vue`, `create/ReservationForm.vue`.

### Migraciones nuevas

Ninguna (`children_count` y `children_age_range` ya existen).

### Seeders

Ninguno.

### Endpoints nuevos/modificados

Ninguno nuevo. `POST /api/home/reservation` cambia solo su payload aceptado (`customer.exactChildrenCount`).

### Riesgos de seguridad a vigilar

- **Mass assignment / input publico:** `POST /api/home/reservation` es **publico sin auth**. El clamp server-side de `exactChildrenCount` es la defensa; verificar que sigue presente y que no se confia en el valor del cliente.
- **Type juggling:** `is_numeric()` mas `intval()` antes del clamp; un `1e5` o `10abc` no debe escapar del rango.
- **XSS:** no aplica (campo numerico).

### Regresiones potenciales

- `Step4.vue` (resumen de precios) usa `selectedKidsCount()`; un error ahi desalinea el precio mostrado vs. el cobrado.
- `extra_children_fee` se calcula desde `children_count` en el flujo publico — un `children_count` mayor puede cambiar el total.
- Emails: la variable `children_count` de las plantillas se alimenta de `children_age_range ?: children_count`; al quedar `children_age_range` vacio, los emails ahora muestran el **numero**, no el rango. Es el comportamiento deseado.

### Orden y dependencias

Primero de la lista. No depende de nada. Nadie depende de A1.

### Notas para el Tester

- Casos limite del clamp: `0`, `-3`, `10`, `11`, `30`, `31`, `999`, `abc`, `null`, ausente, `"7"` (string numerico), float `7.9`.
- Rango desconocido (`99 kids`) implica `$bounds === null`: no debe romper.
- No hay DB: reutilizar el andamiaje de `ReservationServiceChildrenCountTest.php`.

---

## A2 — Editar plantillas de email sin que se reseteen todas

### Estado actual

- **Implementacion: 0%.** `EmailTemplateService::update()` (`app/Services/EmailTemplateService.php:32-45`) hoy solo valida existencia y delega en `EmailTemplateRepository::update($id, $data)`.
- **Working tree:** existe `tests/unit/EmailTemplateServiceUpdateTest.php` **sin commitear**. Esta escrito contra el diseno objetivo y **hoy falla**, porque exige:
  - Que `EmailTemplateService::update()` valide subject (string, no vacio, `mb_strlen` menor o igual a 255), content (string, no vacio, JSON valido) y normalice `is_active` a entero 0/1, lanzando `HTTPException` 400.
  - Que `EmailTemplateRepository::update(string $id, array $data, array $systemFields = [])` acepte un **tercer parametro nuevo**, mergeado DESPUES del filtrado por whitelist (para que el cliente no pueda falsificar `is_customized`).
  - Que systemFields contenga `is_customized` en 1, `customized_at` (formato Y-m-d H:i:s) y `customized_by` (nombre, email o System).
- Ese test es el **contrato de la implementacion**. El Developer debe implementar para satisfacerlo tal cual (no reescribir el test para que pase).
- Seeders que hoy pisan ediciones del admin (15 archivos tocan `email_templates`): `EmailTemplateSeeder`, `AbandonedCartFollowUpEmailSeeder`, `AddAddonsRowToReservationConfirmationSeeder`, `AddPaymentConfirmationTemplateSeeder`, `AddTermsToPaymentNotificationSeeder`, `FixBrandingCapitalizationSeeder`, `FixDurationInEmailsSeeder`, `FixEmailPromoDiscountSeeder`, `FixEmailTemplateBodiesSeeder`, `FixEmailTemplateBrandingSeeder`, `FixLogoSizeSeeder`, `FixPaymentNotificationIntroSpacingSeeder`, `FixPaymentNotificationSubjectSeeder`, `PatchEmailTemplatesSeeder`, `WeekReminderEmailSeeder`.
- `DatabaseSeeder` NO invoca ninguno de ellos: se corren a mano en el VPS. Ese es el origen del bug.

### Decision de producto tomada (congelada)

1. Columna **`is_customized`** en `email_templates`, mas `customized_at` y `customized_by` para trazabilidad.
2. `EmailTemplateService::update()` la marca en 1 en **cada** edicion desde el admin, aunque solo cambie el body.
3. **Todos** los seeders que tocan `email_templates` pasan a politica: insertar si falta; actualizar solo si `is_customized = 0`. Nunca reescriben una plantilla personalizada.
4. Politica de deploy documentada: `EmailTemplateSeeder` queda idempotente y seguro; la familia Fix y Patch queda **archivada** (parche historico ya aplicado, no volver a correr salvo emergencia).
5. **No** se implementa backup automatico de la tabla en el VPS dentro de este flujo (tarea de infra); se documenta como recomendacion de deploy.

### Criterios de aceptacion

1. Migracion nueva agrega `is_customized` (BOOLEAN, NOT NULL, default 0), `customized_at` (DATETIME NULL) y `customized_by` (VARCHAR 255 NULL) a `email_templates`; el metodo down las elimina.
2. `EmailTemplateModel::$allowedFields` y la entidad `EmailTemplate` (casts y dates) incluyen los 3 campos nuevos.
3. `EmailTemplateRepository::update()` acepta un tercer parametro `array $systemFields = []` y lo mergea **despues** del `array_intersect_key` contra su whitelist.
4. Un payload con `is_customized` en 0 o `customized_by` falsificado **no** puede desmarcar ni alterar la marca de personalizacion.
5. `EmailTemplateService::update()` lanza 400 con: subject vacio, solo espacios, no-string o de mas de 255 caracteres; content vacio, no-string o JSON invalido. Acepta subject de exactamente 255 caracteres **multibyte** (`mb_strlen`, no `strlen`).
6. `is_active` se persiste siempre como entero 0 o 1 (truthy: true, 1, "1", "on"; falsy: false, 0, "0", "").
7. `customized_by` toma nombre y apellido del usuario autenticado; si no hay nombre, su email; si no hay usuario, System.
8. Ningun seeder de `email_templates` ejecuta UPDATE sobre una fila con `is_customized = 1`. Correr **todos** los seeders de email dos veces seguidas tras editar una plantilla en el admin deja la edicion intacta.
9. Los seeders siguen insertando cuando el slug no existe.
10. `tests/unit/EmailTemplateServiceUpdateTest.php` pasa **sin modificarlo**.

### Archivos backend a tocar

- `app/Database/Migrations/2026-08-28-010000_AddCustomizationFlagsToEmailTemplates.php` **(nuevo)**
- `app/Models/EmailTemplateModel.php` (allowedFields)
- `app/Entities/EmailTemplate.php` (casts y dates con `customized_at`)
- `app/Repositories/EmailTemplateRepository.php` (firma de update, whitelist, y **agregar las columnas nuevas al select de `getAll()`**, que hoy es explicito)
- `app/Services/EmailTemplateService.php` (validacion mas systemFields)
- `app/Database/Seeds/Support/EmailTemplateSeedGuard.php` **(nuevo, trait — NO es un Seeder)**: metodos `templateIsCustomized(string $slug): bool` y `safeUpdateTemplate(string $slug, array $data): bool`, que retorna false y loguea si la plantilla estaba personalizada. El trait degrada con gracia via `fieldExists` si la columna aun no existe.
- Los 15 seeders listados arriba: usar el trait en cada update sobre `email_templates`. **Sin cambiar el contenido que siembran.**

### Archivos frontend a tocar

- `frontend/src/components/admin/email-templates/EmailTemplates.vue` — badge Customized en el listado.
- `frontend/src/components/admin/email-templates/EmailTemplateEdit.vue` — mostrar quien y cuando edito por ultima vez cuando `customized_at` no sea nulo.

### Migraciones nuevas

`2026-08-28-010000_AddCustomizationFlagsToEmailTemplates`: `is_customized` BOOLEAN NOT NULL DEFAULT 0, `customized_at` DATETIME NULL, `customized_by` VARCHAR(255) NULL.

### Seeders

- **Ningun seeder nuevo obligatorio.** La marca se genera sola en la primera edicion desde el admin.
- **Opcional**, solo si se confirma que ya hay plantillas editadas en produccion antes del deploy: crear `app/Database/Seeds/LockExistingEmailTemplatesSeeder.php` **(nuevo, independiente)** que ponga `is_customized = 1` para una lista explicita de slugs. No se ejecuta por defecto ni forma parte de `DatabaseSeeder`.
- **Prohibido** cambiar el contenido sembrado por los seeders historicos. La unica edicion permitida en ellos es la guarda no destructiva.

### Endpoints nuevos/modificados

`PUT /api/email-templates/{id}` (`EmailTemplateController::updateData`) — misma firma; cambia el comportamiento (400 de validacion y marca de personalizacion). `GET /api/email-templates` devuelve 3 campos mas.

### Riesgos de seguridad a vigilar

- **Mass assignment:** `updateData()` decodifica el body entero y lo pasa al servicio. La defensa es la whitelist del Repository: `is_customized`, `customized_at` y `customized_by` **no** deben estar en esa whitelist (solo en allowedFields del Model, para que systemFields funcione).
- **XSS almacenado:** body y content son HTML que se inyecta en emails y se previsualiza en un iframe con srcdoc. Ya es asi hoy; no relajar nada y mantener el endpoint tras `verifyToken`.
- **JSON invalido:** validar con `json_last_error()`; rechazar content no-string.
- **Authz:** el grupo `email-templates` ya esta bajo `verifyToken`; confirmar que sigue asi.
- **SQLi:** los seeders arman queries con el query builder; no concatenar slugs a mano.

### Regresiones potenciales

- `render()` y `preview()` leen la misma entidad: agregar campos no debe alterar el render de ninguna plantilla.
- Un update que antes pasaba con subject vacio ahora devuelve 400: verificar que el frontend muestre el mensaje y no quede colgado.
- Los seeders con guarda deben poder correr en una DB **sin** la columna nueva (si alguien siembra antes de migrar).
- El select explicito de `getAll()` es la trampa clasica: si no se actualiza, el badge del frontend nunca aparece.

### Orden y dependencias

Segundo. No depende de nada. **B5 depende parcialmente de A2**: el seeder de su plantilla nueva debe nacer ya con la guarda.

### Notas para el Tester

- **Sin DB.** El contrato ya esta en `EmailTemplateServiceUpdateTest.php`; ampliar con: subject de 255 vs 256 caracteres multibyte, content valiendo null, corchetes vacios o cero (todos JSON validos), `is_active` ausente, y dos updates seguidos (`is_customized` sigue en 1 y `customized_at` se actualiza).
- Para el trait de seeders no hay DB: extraer la decision a un metodo puro testeable o usar un doble del query builder.

---

## A3 — "Sigue con eso"

### Estado actual

Nota ambigua del PDF de Jamie, sin contenido accionable. Por posicion aparece debajo de "No sale el tipo de evento" y de "Revisar ninos vs opcion elegida".

### Decision de producto tomada (congelada)

**SIN ACCION DE CODIGO — cubierto por `d3e1ac9` mas A1.**

- Tipo de evento en el ADMIN: resuelto en `d3e1ac9` (Show event type in admin reservation list and detail view).
- Ninos vs opcion elegida: resuelto en A1 (`8709b43`).

No se define alcance de implementacion, no se escriben criterios de aceptacion tecnicos, no se crean tests y no se toca ningun archivo. Si mas adelante se aclara que se referia a otra cosa, sera un requerimiento nuevo fuera de este plan.

### Criterios de aceptacion

No aplica. El Certifier lo cierra verificando unicamente que **no hay cambios en el working tree atribuibles a A3**.

### Archivos backend / frontend / migraciones / seeders / endpoints

Ninguno.

### Riesgos y regresiones

Ninguno.

### Orden y dependencias

Tercero, por orden del PDF. Sin dependencias.

### Notas para el Tester

No ejecutar nada. Registrar como cerrado sin codigo.

---

# PARTE B — Nuevas actualizaciones

## B2 — Agregar opcion de CC en los emails desde la plataforma

### Estado actual

- **Implementacion: 0%.** `BrevoEmailService::sendEmail($to, $subject, $htmlContent)` (`app/Services/BrevoEmailService.php:34-44`) arma un `SendSmtpEmail` con solo `to`. El SDK soporta cc y bcc pero no se usan.
- `ReservationService::sendTemplateEmail()` no acepta destinatarios extra.
- `EmailTemplateController::sendCustomEmail()` manda 1 a 1 sin cc.
- `reservation_email_history` no tiene columna para CC.

### Decision de producto tomada (congelada)

Se adopta la recomendacion "ambos":

1. **CC por defecto global**, configurable por variable de entorno `email.defaultCc` (lista separada por comas). Si esta vacia, no se agrega nada. Se aplica a **todos** los envios transaccionales que pasan por `BrevoEmailService::sendEmail()`.
2. **CC editable por envio** en el compositor del admin (modal de Send email de una reserva y envio masivo de plantillas). Los CC del compositor se **suman** al CC por defecto, sin duplicados.
3. Los CC efectivamente usados se guardan en `reservation_email_history.cc_emails` (string separado por comas).
4. BCC **fuera de alcance** en esta iteracion (la firma lo deja preparado pero la UI no lo expone).

### Criterios de aceptacion

1. `BrevoEmailService::sendEmail(string $to, string $subject, string $htmlContent, array $cc = [], array $bcc = [])`. La clave cc solo se agrega al payload de Brevo cuando el array resultante **no esta vacio** (Brevo rechaza arrays vacios).
2. Los CC se normalizan: trim, minusculas, deduplicados, filtrados con `FILTER_VALIDATE_EMAIL`, y se excluye el destinatario principal para no duplicarlo.
3. **Maximo 10 CC por envio.** Superarlo devuelve 400 con mensaje claro.
4. Una direccion invalida no rompe el envio: se descarta y se loguea; si el cliente mando explicitamente CC invalidos, se responde 400 (el descarte silencioso solo aplica al CC por defecto de config).
5. `email.defaultCc` se lee una sola vez por instancia y se aplica a todos los envios; si no esta definida, el comportamiento es identico al actual.
6. `POST /api/reservations/send-template-email` acepta `cc` (array de strings, opcional) y lo propaga.
7. `POST /api/email-templates/send` acepta `cc` (array de strings, opcional).
8. La fila de `reservation_email_history` del envio guarda los CC en `cc_emails`; el modal de detalle del admin los muestra.
9. Sin `cc` en el payload, el comportamiento y el HTML enviado son byte a byte iguales a los actuales (no regresion).

### Archivos backend a tocar

- `app/Services/BrevoEmailService.php` — nueva firma con cc y bcc, normalizacion y CC por defecto desde `getenv('email.defaultCc')`.
- `app/Services/ReservationService.php` — `sendTemplateEmail(..., array $cc = [])`, propagacion al envio y a `recordEmailHistory` (`cc_emails`).
- `app/Controllers/ReservationController.php` — `sendTemplateEmail()` lee y valida `cc`.
- `app/Controllers/EmailTemplateController.php` — `sendCustomEmail()` lee y valida `cc`.
- `app/Models/ReservationEmailHistoryModel.php` — `cc_emails` en allowedFields.
- `app/Database/Migrations/2026-08-28-020000_AddCcEmailsToReservationEmailHistory.php` **(nuevo)**.
- `env` del VPS: documentar `email.defaultCc` (no commitear valores reales).

### Archivos frontend a tocar

- `frontend/src/components/admin/email-templates/ComposeEmailModal.vue` — campo CC (chips o input de texto separado por comas), validacion de formato en cliente, y envio del array `cc` en ambos payloads (`/reservations/send-template-email` y `/email-templates/send`).
- `frontend/src/components/admin/reservations/ReservationView.vue` — columna o campo CC en el detalle del historial (`selectedHistory.cc_emails`).

### Migraciones nuevas

`2026-08-28-020000_AddCcEmailsToReservationEmailHistory`: `cc_emails` VARCHAR(500) NULL en `reservation_email_history`.

### Seeders

Ninguno. No se toca contenido de plantillas.

### Endpoints nuevos/modificados

- `POST /api/reservations/send-template-email` — nuevo campo opcional `cc: string[]`.
- `POST /api/email-templates/send` — nuevo campo opcional `cc: string[]`.
- Sin rutas nuevas.

### Riesgos de seguridad a vigilar

- **Email header injection:** aunque el SDK de Brevo serializa a JSON, validar cada direccion con `FILTER_VALIDATE_EMAIL` y rechazar cualquier cosa con saltos de linea (`\r`, `\n`) o comas embebidas.
- **Exfiltracion de datos / abuso como relay:** el CC permite mandar el contenido de una reserva a un tercero arbitrario. Mitigacion: endpoint bajo `verifyToken`, tope de 10 CC, y registro obligatorio en `reservation_email_history` con `sent_by` (quien lo hizo queda auditado).
- **XSS:** `cc_emails` se renderiza en el admin — usar interpolacion normal de Vue, nunca `v-html`.
- **Mass assignment:** `cc_emails` entra por `recordEmailHistory` desde el servicio, no desde el request crudo.
- **Longitud:** truncar a 500 caracteres antes de persistir para no fallar el insert.

### Regresiones potenciales

- `BrevoEmailService::sendEmail()` la llaman al menos: `ReservationService` (5 sitios), `ReservationDraftService::sendFollowUpEmail`, `EmailTemplateController::sendCustomEmail`. Los parametros nuevos deben tener default para no romper ninguna.
- Un `email.defaultCc` mal configurado en el VPS haria que **todos** los emails transaccionales lleven copia. Documentarlo bien y dejarlo vacio por defecto.

### Orden y dependencias

Cuarto (primero de la Parte B). No depende de nada. **B3 y B1 se benefician** de su migracion de historial pero no la requieren.

### Notas para el Tester

- **Sin DB.** Testear `BrevoEmailService` extrayendo la normalizacion a un metodo puro (`normalizeRecipients(array $cc, string $to): array`) y testeando ese metodo; el `TransactionalEmailsApi` se sustituye por un doble via `ReflectionProperty` sobre `$apiInstance`.
- Casos limite: cc vacio, cc con el mismo email del destinatario, duplicados con distinto case, 11 direcciones, direccion con salto de linea inyectado, direccion con espacios alrededor, cc con valores no-string (numeros, arrays anidados), `email.defaultCc` con separadores raros (`;`, comas dobles, espacios).
- Verificar que **sin** cc el payload a Brevo no contiene la clave cc.

---

## B3 — Registrar en el historial el envio del link de pago y el pago recibido

### Estado actual

- **Implementacion: parcial (solo el envio manual).** `reservation_email_history` existe (migracion `2026-07-06-010000`), con modelo y endpoint `GET /api/reservations/{id}/email-history`.
- Solo `ReservationService::sendTemplateEmail()` llama a `recordEmailHistory()` (`ReservationService.php:1207`).
- **No registran nada:** `sendPaymentEmail()` (link de pago, envio en `:1143`), `sendPaymentConfirmationEmail()` (`:1624`), `sendConfirmationEmail()` (`:1670`), `sendWeekReminders()` (`:1720`), ni `ReservationDraftService::sendFollowUpEmail()`.
- Bloqueo estructural: `template_id` es `CHAR(36) NOT NULL` con **FK RESTRICT** a `email_templates`. Los envios automaticos renderizan por **slug**, no tienen id de plantilla a mano, y el evento "pago recibido" no es un email.
- `email_body` es `LONGTEXT NOT NULL`.
- Frontend: la pestana Email History de `ReservationView.vue` ya existe con headers fijos (`historyHeaders`, linea ~295).

### Decision de producto tomada (congelada)

La nota de Jamie pide ver "pago recibido" en el historial, asi que el historial pasa a ser un **timeline de la reserva**, pero **sin tabla nueva**: se reutiliza `reservation_email_history` con una columna discriminadora.

1. Nueva columna `event_type` VARCHAR(30) NOT NULL DEFAULT `email`. Valores admitidos en esta iteracion: `email`, `payment`.
2. `template_id` pasa a **NULLABLE** (hay que soltar la FK, alterar la columna y volver a crear la FK).
3. Se registran, con `sent_by = System` cuando son automaticos, los siguientes `template_name`:
   - `Payment Link Sent` (desde `sendPaymentEmail`)
   - `Payment Received` (desde `sendPaymentConfirmationEmail`) — ver B1
   - `Reservation Received` (desde `sendConfirmationEmail`)
   - `Week Reminder` (desde `sendWeekReminders`)
   - `Abandoned Cart Follow-Up` (desde `ReservationDraftService`, **solo si el draft ya tiene reservation_id**; si no, se omite porque la FK a `reservations` es obligatoria)
4. Ademas del email, `handlePaymentCompleted()` inserta una fila de **evento** con `event_type = payment`, `template_name = Payment Received`, y en `email_body` un resumen HTML corto (monto, gratuity, payment intent). Esto satisface literalmente "agregar pago recibido en historial".
5. **Sin backfill** de reservas historicas. Se documenta que el timeline arranca desde el deploy.
6. `status` usa el ENUM ya existente: `Sent` en exito, `Failed` en el catch. La fila de evento de pago siempre es `Sent`.

### Criterios de aceptacion

1. Migracion: `template_id` queda NULLABLE (drop FK, modify column, re-add FK) y se agrega `event_type` VARCHAR(30) NOT NULL DEFAULT `email`, con indice. El metodo down revierte ambas cosas.
2. `ReservationEmailHistoryModel::$allowedFields` incluye `event_type`.
3. `recordEmailHistory()` nunca lanza hacia arriba: se envuelve en try/catch y loguea. **Un fallo del historial jamas debe impedir un envio ni marcar un pago.**
4. `sendPaymentEmail()` registra una fila `Payment Link Sent` con status `Sent`; si el envio falla, registra `Failed` **antes** de relanzar la `HTTPException`.
5. `sendConfirmationEmail()` registra `Reservation Received` con `Sent` o `Failed` segun el resultado del try/catch (hoy se traga la excepcion y solo loguea; ese comportamiento se mantiene).
6. `sendWeekReminders()` registra `Week Reminder` por reserva, con `Failed` cuando el render o el envio fallan; el contador de enviados no cambia de semantica.
7. `handlePaymentCompleted()` inserta la fila `event_type = payment` **despues** de marcar `is_paid` y **solo una vez** (la guarda de idempotencia por `is_paid` ya existente lo garantiza; verificar que webhook y verifyPayment no dupliquen).
8. `GET /api/reservations/{id}/email-history` devuelve las filas nuevas ordenadas por `sent_at DESC` e incluye `event_type`.
9. El admin distingue visualmente los eventos de pago de los emails (icono o badge) y su fila no ofrece un cuerpo de email real sino el resumen.
10. Reservas sin filas nuevas siguen renderizando el historial sin errores.

### Archivos backend a tocar

- `app/Database/Migrations/2026-08-28-030000_AlterReservationEmailHistoryForSystemEvents.php` **(nuevo)**
- `app/Models/ReservationEmailHistoryModel.php` (allowedFields)
- `app/Services/ReservationService.php` — `sendPaymentEmail`, `sendConfirmationEmail`, `sendWeekReminders`, `handlePaymentCompleted`, y endurecer `recordEmailHistory` con try/catch. Conviene un helper privado `recordSystemEmail(string $reservationId, string $name, string $recipient, string $subject, string $body, string $status, string $eventType = 'email')`.
- `app/Services/ReservationDraftService.php` — registro condicional del follow-up (solo con `reservation_id`).

### Archivos frontend a tocar

- `frontend/src/components/admin/reservations/ReservationView.vue` — `historyHeaders` con la columna de tipo, badge por `event_type`, y el modal de detalle adaptando el iframe cuando el evento no es un email.

### Migraciones nuevas

`2026-08-28-030000_AlterReservationEmailHistoryForSystemEvents`
- DROP FOREIGN KEY sobre `template_id`
- MODIFY `template_id` CHAR(36) NULL
- ADD `event_type` VARCHAR(30) NOT NULL DEFAULT 'email' + indice
- ADD FOREIGN KEY `template_id` a `email_templates(id)` con `ON DELETE SET NULL` (RESTRICT ya no sirve con nulos)

### Seeders

Ninguno.

### Endpoints nuevos/modificados

`GET /api/reservations/{id}/email-history` — misma ruta, payload con `event_type` (y `cc_emails` si B2 ya paso).

### Riesgos de seguridad a vigilar

- **IDOR:** `getEmailHistory` ya valida existencia de la reserva y esta bajo `verifyToken`. Confirmar que no se expone por rutas publicas.
- **XSS almacenado:** `email_body` se renderiza en `<iframe srcdoc>`. El resumen HTML del evento de pago lo generamos nosotros: escapar con `esc()` cualquier valor dinamico (payment intent, montos formateados).
- **Fuga de PII:** el historial guarda cuerpos completos de email. No exponerlo en endpoints publicos ni en logs.
- **SQLi:** usar el Model, nunca concatenar.
- **Integridad:** la FK a `reservations` es CASCADE; borrar una reserva borra su timeline (comportamiento actual, aceptado).

### Regresiones potenciales

- La migracion toca una tabla con FK: en MySQL el nombre de la constraint lo genero CI4 (`reservation_email_history_template_id_foreign`). Resolverlo dinamicamente si el nombre difiere, o el deploy revienta.
- Si `recordEmailHistory` empieza a lanzar (por ejemplo `email_body` NOT NULL con string vacio), rompe el flujo de pago. Por eso el criterio 3 es obligatorio: usar un placeholder no vacio.
- Duplicados de "Payment Received" si el webhook y `verifyPayment` corren casi simultaneos: la guarda es `is_paid`, pero hay una ventana de carrera. Aceptada; documentada.
- El volumen de la tabla crece bastante mas rapido que hoy.

### Orden y dependencias

Quinto. **Prerrequisito de B1** (que necesita `template_id` nullable) y **de B6** (que registra el cambio de reserva). Se beneficia de B2 si ya paso (columna `cc_emails`).

### Notas para el Tester

- **Sin DB.** Doblar `ReservationEmailHistoryModel` no es directo porque se instancia con `new` dentro del metodo: extraer la instanciacion a una propiedad o a un metodo protegido `historyModel()` sustituible por Reflection. Indicarselo al Developer como requisito de testabilidad.
- Casos limite: reserva sin email, envio que lanza, historial que lanza (el flujo principal debe sobrevivir), `handlePaymentCompleted` llamado dos veces, `sendWeekReminders` con una reserva que falla y otra que funciona.
- Verificar que el body del evento de pago no contiene HTML sin escapar cuando el `service_name` trae comillas o `<script>`.

---

## B1 — Clientes que no reciben el email despues del pago

### Estado actual

- **Implementacion: 0%.**
- `sendPaymentConfirmationEmail()` (`ReservationService.php:1576`) hace `try { sendEmail } catch { log_message('error', ...) }` en `:1623-1627`. **Se traga la excepcion**, no registra nada visible y no reintenta.
- Se dispara desde `handlePaymentCompleted()` (`:1465`), invocado por el webhook (`ReservationController::stripeWebhook`) y por `verifyPayment()`.
- No hay registro en `reservation_email_history` (ver B3).

### Decision de producto tomada (congelada)

**El alcance implementable en este flujo es SOLO la mejora de codigo:** registrar `sendPaymentConfirmationEmail` en `reservation_email_history` con `status = Sent | Failed` y hacerlo visible en el admin.

**Explicitamente FUERA de este flujo (accion externa, no se implementa ni se testea aqui):**

- Hablar con Cristian sobre la configuracion de infra.
- Verificar en el dashboard de Stripe que el endpoint `POST /api/stripe/webhook` esta registrado y que no hay intentos fallidos.
- Revisar `writable/logs/` del VPS buscando `Failed to send payment confirmation email` para `siyaamunjal@gmail.com` y `celine.klepach@gmail.com`.
- Revisar el log transaccional de Brevo (bounce, spam, o nunca intentado) y la validacion del sender.
- Revisar `brevo.apiKey` y `stripe.webhookSecret` en el `.env` del VPS.
- Cola o reintento automatico de emails fallidos: **diferido**, no entra en esta iteracion.

Estos puntos quedan documentados como acciones de infraestructura a coordinar por fuera. El flujo Developer/Tester/Certifier solo certifica la parte de codigo.

### Criterios de aceptacion

1. `sendPaymentConfirmationEmail()` registra en `reservation_email_history` una fila `Payment Received` con `event_type = email`, `sent_by = System`, `recipient_email` del cliente, subject y body renderizados, y `status = Sent`.
2. Si el envio lanza, registra la **misma fila con `status = Failed`** e incluye el mensaje de error en `email_body` o en el subject (decision: se antepone al `email_body` un bloque escapado con el motivo). El metodo **sigue sin propagar la excepcion** (no debe romper el webhook de Stripe, que reintentaria el pago).
3. Si `reservation->email` esta vacio, se registra una fila `Failed` con motivo "Customer email is empty" en vez de salir en silencio.
4. El log de error existente (`log_message('error', ...)`) se conserva.
5. El admin muestra la fila en la pestana Email History con el badge de estado correspondiente (rojo para Failed).
6. Nada en este cambio puede alterar el resultado de `handlePaymentCompleted()`: la reserva se marca pagada igual, pase lo que pase con el email o con el historial.
7. Se agrega documentacion (comentario en el metodo) apuntando a las acciones externas de infra.

### Archivos backend a tocar

- `app/Services/ReservationService.php` — `sendPaymentConfirmationEmail()` (y el helper `recordSystemEmail` creado en B3).

### Archivos frontend a tocar

Ninguno propio (la visibilidad ya la aporta el trabajo de B3 en `ReservationView.vue`).

### Migraciones nuevas

Ninguna. Reutiliza la de B3 (`template_id` nullable, `event_type`).

### Seeders

Ninguno.

### Endpoints nuevos/modificados

Ninguno.

### Riesgos de seguridad a vigilar

- **Fuga de informacion en el mensaje de error:** el `getMessage()` de una excepcion de Brevo puede incluir fragmentos de la API key o headers. **Sanitizar**: guardar solo la clase de excepcion y un mensaje truncado a 255 caracteres, con `esc()` aplicado.
- **XSS almacenado:** el motivo de fallo se renderiza en el iframe del admin — escapar siempre.
- **Disponibilidad del webhook:** el endpoint de Stripe es publico por diseno y ya valida firma. No debilitar esa validacion; en particular, el nuevo codigo no debe hacer que el webhook devuelva 500 (Stripe reintentaria).

### Regresiones potenciales

- El webhook de Stripe: cualquier excepcion nueva que escape de `sendPaymentConfirmationEmail` haria fallar `stripeWebhook` con 500 y Stripe reintentaria el evento. El criterio 6 es innegociable.
- `verifyPayment()` comparte el mismo camino: la pagina de exito del cliente no debe romperse.
- Doble registro si webhook y `verifyPayment` corren a la vez (misma ventana de carrera que B3).

### Orden y dependencias

Sexto. **Depende de B3** (necesita `template_id` nullable y el helper de registro). No depende de B2.

### Notas para el Tester

- **Sin DB.** Doblar el `BrevoEmailService` (propiedad `emailService` de `ReservationService`) via Reflection para forzar exito y excepcion, y doblar el modelo de historial (requisito de testabilidad definido en B3).
- Casos limite: email vacio, email invalido, excepcion de Brevo con mensaje larguisimo, excepcion con caracteres HTML, `total_amount` nulo, `gratuity_amount` nulo.
- Verificar explicitamente que `sendPaymentConfirmationEmail()` **nunca** lanza, ni siquiera cuando el propio registro de historial falla.

---

## B4 — Automatico de carritos abandonados semanal (7 dias)

### Estado actual

- **Implementacion: 0% del automatico.** El follow-up es 100% manual: `ReservationDraftService::sendFollowUpEmail($id)` (`:186`) manda 1 email a 1 draft con la plantilla `abandoned_cart_followup` y marca `follow_up_sent_at`.
- Se dispara solo desde el admin: `POST /api/reservation-drafts/{id}/follow-up`.
- `ReservationDraftModel::getAbandoned(int $hoursOld = 24)` (`:80`) filtra `completed = 0`, `email IS NOT NULL` y `last_activity_at` antiguo — **pero no filtra `follow_up_sent_at`**, asi que reenviaria a los ya contactados.
- Infraestructura a copiar: `app/Commands/SendWeekReminders.php` (`php spark reminders:week`) mas `ReservationService::sendWeekReminders()` con marca anti-duplicado.
- Plantilla ya sembrada por `AbandonedCartFollowUpEmailSeeder` (slug `abandoned_cart_followup`).
- `follow_up_sent_at` ya existe (migracion `2026-07-03-010000`).

### Decision de producto tomada (congelada)

Se adopta la **opcion consistente con lo que ya existe**: cron propio que envia por la API transaccional de Brevo, igual que el recordatorio semanal. **No** se integran automations nativas ni listas de Brevo (eso seria un requerimiento aparte, mucho mas caro).

Definicion de "abandonado" congelada:

- `completed = 0`
- `email` presente y no vacio
- `last_activity_at` de hace **7 dias o mas**
- `follow_up_sent_at` **IS NULL**
- **Un solo follow-up por draft.** No hay secuencia.

Frecuencia: el comando corre **1 vez al dia**; el filtro de 7 dias mas `follow_up_sent_at` evita spam.

### Criterios de aceptacion

1. Nuevo comando `app/Commands/SendAbandonedCartFollowUps.php`, grupo `Reservations`, nombre `carts:followup`, espejo estructural de `SendWeekReminders` (misma forma de salida por `CLI::write`).
2. Nuevo metodo `ReservationDraftService::sendAbandonedFollowUps(int $daysOld = 7): int` que devuelve la cantidad enviada.
3. Nuevo metodo `ReservationDraftModel::getAbandonedForFollowUp(int $daysOld = 7): array` con los 4 filtros de la definicion congelada, ordenado por `last_activity_at ASC`.
4. `getAbandoned()` (usado por el admin) **no cambia de comportamiento**.
5. Un draft que falla al enviar se loguea y **no** marca `follow_up_sent_at`; el bucle continua con el resto (un fallo no aborta la corrida).
6. Un draft ya enviado nunca se reenvia: correr el comando dos veces seguidas envia 0 la segunda vez.
7. El comando es idempotente y seguro de correr aunque no haya drafts (devuelve 0, sin errores).
8. El email usa la plantilla `abandoned_cart_followup` existente, con las mismas variables (`customer_name`, `resume_url`).
9. Se documenta la linea de crontab a registrar en el VPS (no se automatiza el despliegue del cron desde codigo).

### Archivos backend a tocar

- `app/Commands/SendAbandonedCartFollowUps.php` **(nuevo)**
- `app/Services/ReservationDraftService.php` — nuevo `sendAbandonedFollowUps()`; conviene extraer el cuerpo comun de `sendFollowUpEmail()` a un privado `dispatchFollowUp(object $draft): bool` para no duplicar logica.
- `app/Models/ReservationDraftModel.php` — nuevo `getAbandonedForFollowUp()`.

### Archivos frontend a tocar

Ninguno obligatorio. Opcional: en `frontend/src/components/admin/abandoned-carts/` mostrar `follow_up_sent_at` como "Auto follow-up sent" si aun no se muestra.

### Migraciones nuevas

Ninguna (`follow_up_sent_at` ya existe).

### Seeders

Ninguno nuevo: la plantilla `abandoned_cart_followup` ya la siembra `AbandonedCartFollowUpEmailSeeder`. **No modificar ese seeder.** Si hiciera falta cambiar el texto del email, se crea un seeder nuevo e independiente (y con la guarda de A2).

### Endpoints nuevos/modificados

Ninguno. La superficie nueva es CLI (`php spark carts:followup`).

### Riesgos de seguridad a vigilar

- **Spam / reputacion del dominio:** el mayor riesgo. La marca `follow_up_sent_at` es la unica proteccion; si el update falla despues del envio, el proximo cron reenvia. Mitigacion: marcar y **luego** verificar, y loguear fuerte cuando el update falle (`sendFollowUpEmail` ya lanza `RuntimeException` en ese caso — en el modo batch hay que capturarla y contarla, no abortar).
- **Ejecucion del comando:** los comandos de spark solo corren por CLI; verificar que no queda expuesto por HTTP.
- **PII en logs:** no loguear el cuerpo del email ni datos del cliente mas alla del email y el id.
- **SQLi:** usar el query builder con bindings; `date('Y-m-d H:i:s', strtotime(...))` se genera del lado servidor, `$daysOld` debe castearse a int.
- **DoS involuntario:** limitar el batch (por ejemplo 200 por corrida) para no saturar la API de Brevo ni el rate limit.

### Regresiones potenciales

- `sendFollowUpEmail()` manual: si se refactoriza para compartir codigo, su contrato actual (devuelve el draft actualizado o `false`, y lanza `RuntimeException` si no puede guardar el timestamp) debe conservarse tal cual, porque `ReservationDraftController::sendFollowUp` depende de el.
- `getAbandoned()` la usa el endpoint `GET /api/reservation-drafts/abandoned`: no cambiar su firma ni su semantica de horas.

### Orden y dependencias

Septimo. Sin dependencias tecnicas con el resto. Se beneficia de B3 si se quiere registrar el follow-up en el historial, pero solo aplica a drafts ya convertidos en reserva.

### Notas para el Tester

- **Sin DB.** Doblar `ReservationDraftModel` y `BrevoEmailService` via Reflection (hoy `sendFollowUpEmail` instancia `EmailTemplateService` y `BrevoEmailService` con `new` dentro del metodo: pedir al Developer que los mueva a propiedades inyectables, si no el metodo es inteestable).
- Casos limite: lista vacia, draft sin email, draft con email en blanco, draft `completed = 1`, draft con `follow_up_sent_at` ya seteado, envio que lanza, update del timestamp que falla, `daysOld` = 0 o negativo (debe normalizarse), lote grande.
- Verificar el conteo devuelto: solo cuenta los que efectivamente se enviaron **y** se marcaron.

---

## B5 — Reservas / links de pago personalizados (monto libre y descripcion)

### Estado actual

- **Implementacion: 0%.**
- `StripeService::createCheckoutSession(float $amount, string $customerEmail, string $reservationId, string $description, float $gratuity)` (`:34`) **ya soporta monto y descripcion libres**; los `metadata` hoy solo llevan `reservation_id`.
- Quien la llama (`ReservationService::regeneratePaymentSession`, `:1436`) siempre pasa `total_amount` y la descripcion fija `Event Reservation - <service_name>`.
- No se puede crear una reserva que sea solo "Late fee $75": `create()` y `createFromForm()` exigen servicio, precio, zipcode, etc.
- `stripeWebhook()` (`ReservationController:300-336`) asume siempre `metadata.reservation_id` y llama a `handlePaymentCompleted()`.
- `verifyPayment()` (`ReservationService:1500`) tambien asume `metadata.reservation_id` y lanza 404 si falta.

### Decision de producto tomada (congelada)

Se adopta la **opcion (a): payment link suelto**, entidad ligera propia. **No** se crean reservas fantasma ni line items manuales en `reservations`.

1. Tabla nueva `custom_payment_links`.
2. El link puede opcionalmente asociarse a una reserva (`reservation_id` NULLABLE) — esto es lo que despues consume B6 para cobrar diferencias, pero **no** afecta los totales de la reserva.
3. Stripe: `metadata` con `type = custom_payment_link` y `payment_link_id`. El webhook **ramifica por `metadata.type`** y nunca pasa por `handlePaymentCompleted()`.
4. Email: **plantilla nueva** con slug `custom_payment_link`, sembrada por un **seeder nuevo**. No se reutiliza `payment_notification` (su cuerpo asume campos de reserva).
5. UI: pantalla admin nueva "Payment Links" con listado, creacion (monto, descripcion, nombre y email del cliente, reserva opcional), copiar URL y reenviar email.
6. Los links pagados **no** aparecen en la tabla de reservas: viven en su propia pantalla. Se registran en el historial de la reserva solo cuando tienen `reservation_id`.
7. Sin expiracion propia (Stripe ya expira sus sesiones); se guarda `expires_at` informativo tomado de la sesion.

### Criterios de aceptacion

1. Migracion `custom_payment_links` con: `id` CHAR(36) PK, `reservation_id` CHAR(36) NULL (FK a `reservations`, ON DELETE SET NULL), `customer_name` VARCHAR(150) NULL, `customer_email` VARCHAR(255) NOT NULL, `description` VARCHAR(255) NOT NULL, `amount` DECIMAL(10,2) NOT NULL, `currency` VARCHAR(10) NOT NULL DEFAULT usd, `status` VARCHAR(20) NOT NULL DEFAULT pending, `stripe_session_id` VARCHAR(255) NULL, `stripe_payment_intent_id` VARCHAR(255) NULL, `payment_url` TEXT NULL, `paid_at` DATETIME NULL, `expires_at` DATETIME NULL, `created_by` VARCHAR(255) NULL, `created_at` / `updated_at` DATETIME NULL. Indices en `status`, `stripe_session_id` y `reservation_id`.
2. Validacion en el servicio: `amount` numerico, mayor que 0 y menor o igual a 10000 (tope duro anti-error de tipeo); `description` no vacia y de maximo 255; `customer_email` valido; `reservation_id`, si viene, debe existir.
3. Crear un link genera la sesion de Stripe y persiste `stripe_session_id`, `payment_url` y `status = pending`. Si Stripe falla, **no** queda fila huerfana (o se borra, o se crea despues de Stripe).
4. El webhook `checkout.session.completed` con `metadata.type = custom_payment_link` marca el link como `paid`, guarda `stripe_payment_intent_id` y `paid_at`, y **no** toca ninguna reserva.
5. El webhook con `metadata.reservation_id` (sin `type`) sigue comportandose **exactamente** como hoy.
6. `verifyPayment()` reconoce ambos tipos y no lanza 404 para un link custom.
7. Marcar pagado es idempotente: dos entregas del webhook dejan `paid_at` de la primera.
8. Enviar el email usa la plantilla `custom_payment_link` con variables `customer_name`, `description`, `amount`, `payment_url`.
9. Si el link tiene `reservation_id`, el envio y el pago se registran en `reservation_email_history` (B3) con `template_name` = `Custom Payment Link Sent` / `Custom Payment Received`.
10. La pantalla admin lista los links con status, monto, descripcion, cliente y fecha; permite copiar la URL y reenviar el email.

### Archivos backend a tocar

- `app/Database/Migrations/2026-08-28-040000_CreateCustomPaymentLinksTable.php` **(nuevo)**
- `app/Models/CustomPaymentLinkModel.php` **(nuevo)** — UUID en `beforeInsert`, `protectFields = true`, allowedFields acotados.
- `app/Entities/CustomPaymentLink.php` **(nuevo, opcional pero coherente con el resto)**
- `app/Repositories/CustomPaymentLinkRepository.php` **(nuevo)** — whitelist propia.
- `app/Services/CustomPaymentLinkService.php` **(nuevo)** — validacion, creacion de sesion Stripe, envio de email, marcado de pago.
- `app/Controllers/CustomPaymentLinkController.php` **(nuevo)**
- `app/Services/StripeService.php` — nuevo metodo `createCustomCheckoutSession(float $amount, string $customerEmail, string $linkId, string $description)` con `metadata` propia, o parametro `array $metadata = []` en el metodo actual (**preferido**, menos duplicacion; mantener retrocompatibilidad de la firma).
- `app/Controllers/ReservationController.php` — `stripeWebhook()` ramifica por `metadata.type`.
- `app/Services/ReservationService.php` — `verifyPayment()` ramifica por `metadata.type`.
- `app/Config/Routes.php` — grupo nuevo.

### Archivos frontend a tocar

- `frontend/src/components/admin/payment-links/PaymentLinks.vue` **(nuevo)** — listado.
- `frontend/src/components/admin/payment-links/PaymentLinkCreate.vue` **(nuevo)** — formulario y copiado de URL.
- Router del admin y menu lateral.

### Migraciones nuevas

`2026-08-28-040000_CreateCustomPaymentLinksTable` (esquema en el criterio 1).

### Seeders

- `app/Database/Seeds/CustomPaymentLinkEmailSeeder.php` **(nuevo, independiente)** — inserta la plantilla slug `custom_payment_link` **solo si no existe**, y respeta la guarda `is_customized` de A2. No modifica ninguna plantilla existente.
- `app/Database/Seeds/PaymentLinksMenuSeeder.php` **(nuevo, independiente)** — item de menu y permisos por rol, siguiendo el patron de `PromoCodesAndAbandonedCartsMenuSeeder`. **No** editar `MenuSeeder` ni `RoleMenuPermissionSeeder`.

### Endpoints nuevos/modificados

Nuevos, bajo `verifyToken`:
- `GET /api/payment-links` — listado
- `POST /api/payment-links` — crear (genera sesion Stripe)
- `GET /api/payment-links/(:segment)` — detalle
- `POST /api/payment-links/(:segment)/send-email` — enviar o reenviar el link por email
- `POST /api/payment-links/(:segment)/cancel` — cancelar (status `cancelled`)
- `DELETE /api/payment-links/(:segment)` — eliminar

Modificados: `POST /api/stripe/webhook` y `GET /api/stripe/verify-payment` (ramificacion, sin cambio de contrato).

### Riesgos de seguridad a vigilar

- **Manipulacion de monto:** el monto se define **solo** en el servidor al crear el link y se persiste; el checkout lo toma de la fila, nunca de un parametro de la URL de pago. Jamas aceptar el monto desde la pagina de exito.
- **Authz:** crear links de pago es una operacion sensible. Debe requerir `verifyToken` **y** el permiso de menu correspondiente (patron `useMenuPermissions` en el front, y verificacion server-side si el resto de modulos la hace).
- **IDOR:** los ids son UUID v4; aun asi, todos los endpoints van tras auth. La `payment_url` de Stripe es el unico secreto compartido con el cliente (comportamiento estandar de Stripe).
- **Webhook:** la verificacion de firma ya existe y **no** se toca. La ramificacion por `metadata.type` debe hacerse **despues** de verificar la firma, y `metadata` debe tratarse como no confiable (validar que el `payment_link_id` existe en nuestra DB).
- **Replay / idempotencia:** guardar `stripe_payment_intent_id` y no reprocesar si `status` ya es `paid`.
- **XSS:** `description` es texto libre del admin que va al email y a la UI: escapar con `esc()` en el HTML del email y usar interpolacion normal en Vue.
- **Mass assignment:** `status`, `paid_at`, `stripe_*` **no** deben estar en la whitelist del Repository para creacion/actualizacion desde el request; se setean solo desde el servicio.
- **Tope de monto:** el limite de 10000 evita cobros catastroficos por error de tipeo.

### Regresiones potenciales

- El webhook es el punto mas critico del sistema. Cualquier cambio ahi puede romper el marcado de pagos de reservas reales. Regla: si `metadata.type` no es `custom_payment_link`, el flujo debe ser **identico byte a byte** al actual.
- `verifyPayment()` lo consume la pagina publica de exito: no debe empezar a lanzar excepciones nuevas.
- `StripeService::createCheckoutSession()` la usan `createFromForm` y `regeneratePaymentSession`: agregar un parametro **al final y con default**.
- El menu nuevo puede quedar invisible si no se siembran permisos por rol.

### Orden y dependencias

Octavo. **Depende de A2** (guarda de seeders para su plantilla nueva) y **se apoya en B3** (registro en historial cuando hay `reservation_id`). **Es prerrequisito de B6.**

### Notas para el Tester

- **Sin DB.** Doblar `StripeService` (que hoy se resuelve por `getStripeService()`, ya sustituible) y el repositorio nuevo via Reflection. El servicio nuevo debe nacer con las dependencias en propiedades para poder doblarlas: pedirselo al Developer.
- Casos limite del monto: `0`, `-5`, `0.001`, `10000`, `10000.01`, string `"50"`, `null`, notacion cientifica, monto con 3 decimales (redondeo a centavos en `(int) round($amount * 100)`).
- Descripcion: vacia, 255 caracteres, 256, con HTML, con emojis.
- Webhook: evento sin `metadata`, con `type` desconocido, con `payment_link_id` inexistente, evento duplicado, evento de reserva normal (no regresion).
- Email: cliente sin email, plantilla ausente (debe fallar con mensaje claro, no con un 500 opaco).

---

## B6 — Modificar servicios / agregar add-ons con la reserva ya creada

### Estado actual

- **Implementacion: 0% del recalculo.**
- Existe el CRUD de `reservation-addons` (`Routes.php:198-206`), `ReservationAddonModel`, `ReservationAddonService`, `ReservationAddonController`, y `ReservationEdit.vue` en el admin.
- **Pero** los totales (`base_price`, `addons_total`, `extra_children_fee`, `travel_fee`, `expedite_fee`, `discount_amount`, `total_amount`, `duration_hours`) se calculan **una sola vez** al crear, con logica **inline y duplicada** en `create()` (`:169-255`) y en `createFromForm()` (`:403-600`). No existe ningun metodo que recalcule.
- Helpers ya extraidos y reutilizables: `calculateAddonsTotal()` (`:1011`), `calculateTotalDuration()` (`:1027`), `calculateSurcharge()` (`:1054`), `resolveTravelFee()` (`:990`), `determinePriceType()` (`:901`).
- `ReservationAddonService::create/update/delete` no disparan nada.
- `updateGratuity()` (`:1683`) bloquea con 400 si `is_paid`. `regeneratePaymentSession()` (`:1427`) tambien.
- `sendConfirmationEmail()` arma los add-ons con `buildAddonsRow($reservation->id)` leyendo la tabla, asi que un email reenviado si reflejaria los cambios.
- No existe ningun campo de saldo: la reserva no sabe cuanto se cobro realmente.

### Decision de producto tomada (congelada)

1. **Refactor primero:** extraer la logica de precios a un metodo privado reutilizable `computeReservationPricing(array $ctx): array` que devuelva el desglose completo. `create()`, `createFromForm()` y el recalculo lo usan. El refactor **no puede cambiar ni un centavo** de los resultados actuales.
2. Nuevo `ReservationService::recalculateTotals(string $reservationId): object` que relee servicio, add-ons, zipcode y promo desde la DB y recompone todos los campos de precio y `duration_hours`.
3. `ReservationAddonService::create/update/delete` disparan el recalculo de la reserva afectada.
4. `ReservationEdit.vue` permite cambiar `service_price_id` y recalcula.
5. **Reserva ya pagada (decision congelada): opcion (a) apoyada en B5.** No se bloquea la modificacion. Se introducen dos campos en `reservations`:
   - `amount_paid` DECIMAL(10,2) NULL — se fija en `handlePaymentCompleted()` como `total_amount + gratuity_amount` al momento del cobro.
   - `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0 — recalculado como `total_amount - amount_paid` (0 si es negativo).
   Si tras el recalculo `balance_due > 0`, el admin puede generar un **link de pago custom (B5)** por la diferencia con un clic; el link nace con `reservation_id` apuntando a la reserva. Si el resultado es **negativo** (hay que devolver dinero), **no se automatiza ningun reembolso**: se muestra una alerta "Refund required: $X" en el admin y se resuelve manualmente en Stripe.
6. Email: **nueva plantilla** slug `reservation_updated` con el desglose actualizado, sembrada por un **seeder nuevo**. El envio es **manual** (boton en el admin), no automatico, para no bombardear al cliente en cada ajuste.
7. Cada recalculo registra un evento en el historial (B3) con `template_name = Reservation Updated` y el delta de total.

### Criterios de aceptacion

1. `computeReservationPricing()` existe y `create()` y `createFromForm()` la usan. **Los tests existentes (`ReservationServiceCreateTest`, `ReservationServiceChildrenCountTest`, `ReservationEmailDurationTest`) siguen pasando sin modificarse.** Ese es el criterio de "el refactor no cambio precios".
2. `recalculateTotals($id)` recalcula `base_price`, `addons_total`, `extra_children_fee`, `travel_fee`, `expedite_fee`, `expedition_fee`, `duration_hours`, `price_type`, `total_amount` y `balance_due`, y devuelve la reserva actualizada.
3. El recalculo **respeta el promo code ya aplicado** y su regla de exclusiones vigente (`4169b7a`: Custom Song, travel fee y expedite fee no reciben descuento). Un recalculo no debe volver a incrementar el contador de uso del promo code.
4. `expedite_fee` (recargo por fecha proxima) se recalcula con la **fecha del evento**, no con la fecha del recalculo, para que no cambie de forma retroactiva por el mero paso del tiempo.
5. Crear, actualizar o borrar un add-on de una reserva dispara el recalculo y devuelve los totales nuevos en la respuesta.
6. Si `is_paid = 1`: la modificacion se permite; `balance_due` refleja la diferencia; si es negativa, se guarda 0 en `balance_due` y el admin ve la alerta de reembolso.
7. `amount_paid` se fija en `handlePaymentCompleted()`; las reservas historicas quedan con `amount_paid` NULL y se tratan como `total_amount` vigente al momento del primer recalculo (documentado, sin backfill masivo).
8. Cambiar `service_price_id` desde `ReservationEdit.vue` recalcula base, duracion, performers y travel fee.
9. El admin muestra el desglose actualizado y, si `balance_due > 0`, un boton para generar el link de pago por la diferencia (B5).
10. El email `reservation_updated` se envia solo por accion explicita del admin y queda registrado en el historial.
11. `updateGratuity()` y `regeneratePaymentSession()` conservan sus bloqueos actuales para reservas pagadas (no se relajan en esta iteracion).

### Archivos backend a tocar

- `app/Services/ReservationService.php` — **refactor grande**: extraer `computeReservationPricing()`, agregar `recalculateTotals()`, setear `amount_paid` en `handlePaymentCompleted()`.
- `app/Services/ReservationAddonService.php` — disparar recalculo en create/update/delete.
- `app/Controllers/ReservationAddonController.php` — devolver los totales nuevos.
- `app/Repositories/ReservationRepository.php` / `app/Models/ReservationModel.php` — `amount_paid` y `balance_due` en allowedFields y whitelist.
- `app/Database/Migrations/2026-08-28-050000_AddBalanceFieldsToReservations.php` **(nuevo)**
- `app/Controllers/ReservationController.php` — endpoint de recalculo manual y de envio del email de actualizacion.

### Archivos frontend a tocar

- `frontend/src/components/admin/reservations/ReservationEdit.vue` — selector de `service_price_id`, refresco de totales, alerta de saldo o reembolso, boton de generar link por la diferencia y boton de enviar el email de actualizacion.
- `frontend/src/components/admin/reservations/ReservationView.vue` — mostrar `amount_paid` y `balance_due`.
- `frontend/src/components/admin/reservations/create/ReservationAddons.vue` y `ReservationTotal.vue` — reflejar los totales recalculados.

### Migraciones nuevas

`2026-08-28-050000_AddBalanceFieldsToReservations`: `amount_paid` DECIMAL(10,2) NULL, `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00 en `reservations`.

### Seeders

- `app/Database/Seeds/ReservationUpdatedEmailSeeder.php` **(nuevo, independiente)** — inserta la plantilla slug `reservation_updated` **solo si no existe**, con la guarda de A2. No toca plantillas existentes.
- **Prohibido** modificar `EmailTemplateSeeder` u otro seeder historico para agregar esta plantilla.

### Endpoints nuevos/modificados

- `POST /api/reservations/(:segment)/recalculate` **(nuevo, verifyToken)** — fuerza el recalculo y devuelve el desglose.
- `POST /api/reservations/(:segment)/send-update-email` **(nuevo, verifyToken)** — envia la plantilla `reservation_updated`.
- `POST|PUT|DELETE /api/reservation-addons/...` — sin cambio de contrato; la respuesta pasa a incluir los totales recalculados.

### Riesgos de seguridad a vigilar

- **Manipulacion de precios (el riesgo mayor del proyecto):** el recalculo debe leer precios de la **base de datos** (`service_prices`, `addons`, `zipcodes`, `promo_codes`), nunca de valores enviados por el cliente. Ni un solo importe puede venir del request.
- **Mass assignment:** `total_amount`, `base_price`, `balance_due`, `amount_paid`, `is_paid`, `paid_at` **no** deben poder setearse desde `PUT /api/reservations/{id}`. Revisar la whitelist de `ReservationRepository` — es un punto de exposicion existente que este requerimiento agrava.
- **Authz / IDOR:** el recalculo y el envio de email exigen `verifyToken` mas permiso de actualizacion de reservas. `ReservationAddonService` valida UUID v4 estricto — mantenerlo.
- **Condicion de carrera:** dos admins editando add-ons de la misma reserva simultaneamente pueden pisar totales. Envolver el recalculo en una transaccion (`$db->transStart()` / `transComplete()`), como ya hace `createFromForm()`.
- **Cobro doble:** generar dos links de saldo por la misma diferencia. Mitigacion: al generar el link, marcar el importe cubierto y no permitir un segundo link pendiente para la misma reserva.
- **XSS:** el desglose del email de actualizacion se arma con concatenacion HTML como el resto de emails: usar `esc()` en cada valor dinamico.
- **Precision decimal:** usar `round(..., 2)` consistentemente; nunca comparar floats con `==` para decidir si hay saldo (usar una tolerancia de 0.01).

### Regresiones potenciales

- **La mas seria de todo el plan.** El refactor de precios toca el core que ya sufrio 6 correcciones recientes (`e562a01` travel fee, `9cc63a8` expedite fee, `5ba2d82` duracion y zipcode, `4169b7a` exclusiones de promo, `9d49b21` separacion de fees, `11aeeb9` entertainment start time). Cada una de esas reglas debe sobrevivir intacta.
- Reglas que **no** pueden perderse en el refactor: zona `minimum_2h` eleva la duracion base a 2h; `resolveTravelFee` multiplica por performers; el recargo por fecha es 20% a menos de 2 dias y 10% entre 2 y 7; `expedition_fee` = `travel_fee + expedite_fee`; el descuento del promo aplica solo a la base.
- `ReservationAddonController` lo consumen pantallas del admin: si empieza a devolver otra forma de payload, revisar los consumidores.
- El recalculo automatico en cada operacion de add-on puede sorprender: una reserva pagada puede pasar a mostrar saldo pendiente sin aviso. Cubrirlo con la alerta de UI.
- Reservas historicas sin `amount_paid`: no deben mostrar saldos falsos.

### Orden y dependencias

Noveno y ultimo. **Depende de B5** (link de pago por la diferencia) y **de B3** (registro del cambio en el historial). Tambien se apoya en A2 para el seeder de su plantilla nueva.

### Notas para el Tester

- **Sin DB.** El foco es `computeReservationPricing()`: es una funcion **pura** si se disena bien (entra un array de contexto, sale un array de importes). Insistir al Developer en esa firma; es lo que hace testeable todo el requerimiento.
- Batería obligatoria de no-regresion de precios, comparando el resultado del metodo nuevo contra los valores que hoy producen `create()` y `createFromForm()`: sin add-ons; con add-ons de cantidad mayor que 1; con suboptions; zona standard, `travel_fee` y `minimum_2h`; 1 vs varios performers; evento a 1, 3 y 30 dias vista; con y sin promo code; promo con Custom Song presente (exclusion); ninos extra por encima del incluido.
- Recalculo: reserva sin add-ons, reserva con add-ons borrados todos, reserva pagada con saldo positivo, con saldo negativo, con saldo exactamente 0.01, reserva sin `service_price_id`, reserva con zipcode borrado.
- Verificar que el contador de uso del promo code **no** se incrementa en el recalculo.

---

# ORDEN DE PROCESAMIENTO (definitivo)

Procesamiento **secuencial**, un requerimiento a la vez. Ningun requerimiento arranca hasta que el anterior este certificado.

| # | REQ | Por que va aqui |
|---|---|---|
| 1 | **A1** | Ya implementado en `8709b43`. Solo verificacion y cierre. Arranca el flujo sin riesgo. |
| 2 | **A2** | Bug de perdida de datos en cada deploy. Su test ya esta escrito (working tree). Prerrequisito blando de B5 y B6 (guarda de seeders para plantillas nuevas). |
| 3 | **A3** | Cierre administrativo sin codigo. Se procesa en su lugar por orden del PDF. |
| 4 | **B2** | Primera feature. Aislada, sin dependencias, migracion pequena sobre `reservation_email_history` (la misma tabla que B3 va a alterar despues: mejor tocarla primero en el orden simple). |
| 5 | **B3** | Habilita el timeline: `template_id` nullable mas `event_type`. **Prerrequisito duro de B1 y de B6.** |
| 6 | **B1** | Necesita la infraestructura de B3. Alcance de codigo minimo; el resto es infra externa. |
| 7 | **B4** | Independiente. Se pone despues de B1/B3 porque no bloquea a nadie y su patron (`SendWeekReminders`) ya existe. |
| 8 | **B5** | Feature completa de punta a punta. **Prerrequisito duro de B6.** Depende de A2 para su seeder de plantilla. |
| 9 | **B6** | El mas caro y riesgoso. Depende de B5 (cobro de diferencias) y de B3 (registro de cambios). Va al final para que el refactor de precios tenga toda la red de tests ya construida. |

## Grafo de dependencias

```text
A1  (independiente)
A2  (independiente) ──────────┬──> B5 (seeder con guarda)
A3  (sin codigo)              └──> B6 (seeder con guarda)
B2  (independiente)
B3  (independiente) ──┬──> B1
                      └──> B6
B4  (independiente)
B5  ─────────────────────> B6
```

## Reglas transversales para Developer / Tester / Certifier

1. **Un requerimiento a la vez.** Un commit por requerimiento, con mensaje descriptivo y los trailers de Co-Authored-By y Claude-Session.
2. **Nunca modificar un seeder historico para cambiar datos.** Unica excepcion documentada: la guarda no destructiva de A2.
3. **Ningun test toca la base de datos.** `phpunit.xml.dist` no tiene DB de tests configurada.
4. **`composer test` en verde antes de cerrar cada requerimiento**, incluidos los tests preexistentes.
5. **Los tests existentes no se modifican** para hacer pasar codigo nuevo. Si un test preexistente falla, es una regresion real.
6. **`tests/unit/EmailTemplateServiceUpdateTest.php` es un contrato**: se implementa contra el, no se reescribe.
7. **Frontend:** despues de tocar `frontend/src` hay que reconstruir (`npm run build` en `frontend/`) porque `public/build/assets` esta versionado y el manifest lo consume `HomeController`. El working tree ya trae 3 assets sin commitear.
8. **Migraciones:** siempre con `down()` funcional; nombre con timestamp posterior al ultimo existente (`2026-07-10-010000`).
9. **Testabilidad:** cuando un servicio instancie dependencias con `new` dentro de un metodo (patron actual en `ReservationDraftService::sendFollowUpEmail` y `ReservationService::recordEmailHistory`), moverlas a propiedades para poder doblarlas por Reflection.

# TABLA DE ESTADO INICIAL

| REQ | Titulo | Estado inicial | Evidencia | Migracion | Seeder nuevo | Frontend |
|---|---|---|---|---|---|---|
| **A1** | Numero de ninos y edades del segundo formulario | **Implementado — pendiente de verificar** | commit `8709b43` + `tests/unit/ReservationServiceChildrenCountTest.php` | No | No | Ya hecho |
| **A2** | Plantillas de email se resetean | **Pendiente — test escrito, implementacion 0%** | `tests/unit/EmailTemplateServiceUpdateTest.php` sin commitear; `EmailTemplateService.php:32-45` sin validacion | Si (1) | Opcional (`LockExistingEmailTemplatesSeeder`) | Si (badge y auditoria) |
| **A3** | "Sigue con eso" | **Cerrado sin codigo** | `d3e1ac9` + A1 | No | No | No |
| **B1** | Email post-pago no llega | **Pendiente — solo mejora de codigo** | `ReservationService.php:1623-1627` traga la excepcion | No (usa la de B3) | No | No (lo cubre B3) |
| **B2** | CC en los emails | **Pendiente — 0%** | `BrevoEmailService.php:34-44` solo arma `to` | Si (1, `cc_emails`) | No | Si (ComposeEmailModal, ReservationView) |
| **B3** | Historial: link de pago y pago recibido | **Pendiente — parcial (solo envio manual)** | solo `sendTemplateEmail` llama a `recordEmailHistory` (`:1207`); `template_id` NOT NULL con FK RESTRICT | Si (1, altera la tabla) | No | Si (ReservationView) |
| **B4** | Carritos abandonados automatico semanal | **Pendiente — 0% del automatico** | follow-up solo manual (`ReservationDraftService:186`); `getAbandoned` no filtra `follow_up_sent_at` | No | No | Opcional |
| **B5** | Links de pago personalizados | **Pendiente — 0%** | `StripeService` ya soporta monto y descripcion libres; nadie los usa | Si (1 tabla nueva) | Si (2: plantilla y menu) | Si (pantalla nueva) |
| **B6** | Modificar servicios / add-ons post-reserva | **Pendiente — 0%** | no existe `recalculateTotals`; logica de precios inline y duplicada en `create()` y `createFromForm()` | Si (1, `amount_paid` y `balance_due`) | Si (1: plantilla `reservation_updated`) | Si (ReservationEdit y ReservationView) |

## Resumen de artefactos nuevos previstos

**Migraciones (5):**
1. `2026-08-28-010000_AddCustomizationFlagsToEmailTemplates` (A2)
2. `2026-08-28-020000_AddCcEmailsToReservationEmailHistory` (B2)
3. `2026-08-28-030000_AlterReservationEmailHistoryForSystemEvents` (B3)
4. `2026-08-28-040000_CreateCustomPaymentLinksTable` (B5)
5. `2026-08-28-050000_AddBalanceFieldsToReservations` (B6)

**Seeders nuevos (3, todos independientes):**
1. `LockExistingEmailTemplatesSeeder` (A2, **opcional**)
2. `CustomPaymentLinkEmailSeeder` + `PaymentLinksMenuSeeder` (B5)
3. `ReservationUpdatedEmailSeeder` (B6)

**Comandos CLI nuevos (1):** `SendAbandonedCartFollowUps` (`php spark carts:followup`) (B4)

**Endpoints nuevos (8):** 6 de `payment-links` (B5) y 2 de reservas (`recalculate`, `send-update-email`) (B6).

**Estado del working tree al momento de este plan:**

```text
?? REQUERIMIENTOS_PENDIENTES.md
?? public/build/assets/main-BiVE9Na7.css
?? public/build/assets/main-DrJYSYnF.js
?? public/build/assets/quill-DBX420AE.js
?? tests/unit/EmailTemplateServiceUpdateTest.php   <- contrato de A2
```

Los 3 assets de `public/build/assets` son artefactos de build sin commitear: decidir en el commit de A1 si se incluyen o se regeneran.

---

**FIN DEL PLAN MAESTRO.**
