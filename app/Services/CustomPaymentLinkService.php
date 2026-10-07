<?php

namespace App\Services;

use App\Models\ReservationEmailHistoryModel;
use App\Repositories\AddonRepository;
use App\Repositories\CustomPaymentLinkItemRepository;
use App\Repositories\CustomPaymentLinkRepository;
use App\Repositories\CustomServiceRepository;
use App\Services\PaymentAccessService;
use App\Repositories\ReservationRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use Ramsey\Uuid\Uuid;

/**
 * B5 — Custom payment links (arbitrary amount + free description).
 *
 * Supplemental payment entity attached to an already-paid reservation. Paying
 * it never touches the reservation totals or its original invoice. The amount
 * is defined here, on the server, and the Stripe
 * checkout reads it from the persisted row — never from a URL parameter.
 *
 * Testability: every collaborator lives in a property (or a lazy getter for
 * StripeService, mirroring ReservationService::getStripeService()) so tests can
 * swap doubles by Reflection without a database.
 */
class CustomPaymentLinkService
{
    /** Hard cap to prevent catastrophic typo charges (acceptance criterion 2). */
    public const MAX_AMOUNT = 10000.0;

    /** Slug of the email template seeded by CustomPaymentLinkEmailSeeder. */
    private const TEMPLATE_SLUG = 'custom_payment_link';

    protected CustomPaymentLinkRepository $repo;
    protected ReservationRepository $reservationRepository;
    protected EmailTemplateService $emailTemplateService;
    protected BrevoEmailService $emailService;

    /** @var StripeService|null Lazy — see getStripeService(). */
    protected $stripeService = null;

    /** @var ReservationEmailHistoryModel|null Lazy — see historyModel(). */
    protected $historyModel = null;

    /** @var PaymentAccessService|null Lazy — see getAccessService(). */
    protected $accessService = null;

    /** @var ReservationService|null Lazy to avoid circular construction. */
    protected $reservationService = null;

    /** @var CustomPaymentLinkItemRepository|null Lazy — see itemRepository(). */
    protected $itemRepository = null;

    /** @var AddonRepository|null Lazy — catálogo de add-ons para validar ítems. */
    protected $addonRepository = null;

    /** @var CustomServiceRepository|null Lazy — catálogo de servicios personalizados. */
    protected $customServiceRepository = null;

    /** Tipos de ítem permitidos en un link y su etiqueta visible. */
    private const ITEM_TYPES = [
        'addon'          => 'Add-on',
        'custom_service' => 'Custom service',
    ];

    public function __construct()
    {
        $this->repo                  = new CustomPaymentLinkRepository();
        $this->reservationRepository = new ReservationRepository();
        $this->emailTemplateService  = new EmailTemplateService();
        $this->emailService          = new BrevoEmailService();
    }

    protected function getStripeService(): StripeService
    {
        if ($this->stripeService === null) {
            $this->stripeService = new StripeService();
        }

        return $this->stripeService;
    }

    protected function historyModel(): ReservationEmailHistoryModel
    {
        if ($this->historyModel === null) {
            $this->historyModel = new ReservationEmailHistoryModel();
        }

        return $this->historyModel;
    }

    protected function getAccessService(): PaymentAccessService
    {
        if ($this->accessService === null) {
            $this->accessService = new PaymentAccessService();
        }

        return $this->accessService;
    }

    protected function itemRepository()
    {
        if ($this->itemRepository === null) {
            $this->itemRepository = new CustomPaymentLinkItemRepository();
        }

        return $this->itemRepository;
    }

    protected function addonRepository()
    {
        if ($this->addonRepository === null) {
            $this->addonRepository = new AddonRepository();
        }

        return $this->addonRepository;
    }

    protected function customServiceRepository()
    {
        if ($this->customServiceRepository === null) {
            $this->customServiceRepository = new CustomServiceRepository();
        }

        return $this->customServiceRepository;
    }

    protected function getReservationService(): ReservationService
    {
        if ($this->reservationService === null) {
            $this->reservationService = new ReservationService();
        }

        return $this->reservationService;
    }

    /**
     * Attach the platform gateway URL (`/pay/{token}`) that admins should copy
     * / see instead of the raw Stripe URL. Reuses the currently active token
     * rather than renewing it — renewal only happens when an email actually
     * goes out (see dispatchLinkEmail()). Only meaningful while the link is
     * still collectable.
     */
    private function attachAccessUrl(object $link): object
    {
        $link->access_url = null;
        $link->items = $this->itemRepository()->getByLink((string) $link->id);

        if ($link->status === 'pending') {
            try {
                $link->access_url = $this->getAccessService()->ensureLink('custom_payment_link', (string) $link->id);
            } catch (\Throwable $e) {
                // Best-effort: a gateway-token hiccup must never break listing
                // or fetching a link. The admin loses the "copy URL" shortcut
                // for this row, not the whole page.
                log_message('error', 'Custom payment link: access URL lookup failed: ' . $e->getMessage());
            }
        }

        return $link;
    }

    // ---------------------------------------------------------------------
    // Queries
    // ---------------------------------------------------------------------

    /**
     * @return object[]
     */
    public function listLinks(): array
    {
        return array_map(fn (object $link) => $this->attachAccessUrl($link), $this->repo->getAll());
    }

    public function getLink(string $id): object
    {
        $link = $this->repo->findById($id);

        if (!$link) {
            throw new HTTPException('Payment link not found', Response::HTTP_NOT_FOUND);
        }

        return $this->attachAccessUrl($link);
    }

    /** @return object[] Links belonging to one reservation, newest first. */
    public function listLinksForReservation(string $reservationId): array
    {
        return array_map(
            fn (object $link) => $this->attachAccessUrl($link),
            $this->repo->getByReservation($reservationId)
        );
    }

    // ---------------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------------

    /**
     * Validate the payload, create the Stripe Checkout Session, then persist the
     * row. The row is written AFTER Stripe succeeds, so a Stripe failure can
     * never leave an orphan link (acceptance criterion 3).
     *
     * @param array  $data      customer_name, customer_email, description, amount,
     *                           reservation_id (optional), currency (optional)
     * @param string $createdBy Human-readable identity of the admin (audit only).
     */
    public function createLink(array $data, string $createdBy = 'System'): object
    {
        [$items, $amount, $extraAmount] = $this->resolvePricing($data);
        $description = $this->assertValidDescription($data['description'] ?? null);
        $email       = $this->assertValidEmail($data['customer_email'] ?? null);
        $name        = $this->normalizeName($data['customer_name'] ?? null);
        $reservationId = $this->assertValidReservationId($data['reservation_id'] ?? null);
        $currency    = $this->normalizeCurrency($data['currency'] ?? null);

        $reservation = $this->reservationRepository->getById($reservationId);
        if (!$reservation || $reservation->status === 'cancelled' || empty($reservation->is_paid)) {
            throw new HTTPException('The reservation cannot receive a payment link', Response::HTTP_BAD_REQUEST);
        }

        // B6 double-charge guard: never issue a second pending link for the same
        // reservation (e.g. two admins generating a balance link at once). The
        // lookup itself must not hard-fail link creation, but a real hit is a
        // hard stop.
        $pending = $this->repo->findPendingByReservation($reservationId);
        if ($pending) {
            throw new HTTPException(
                'A pending personalized payment link already exists. Resend it or cancel it before creating a new one.',
                Response::HTTP_CONFLICT
            );
        }

        // Pre-generate the id so it can travel in the Stripe metadata before the
        // row exists.
        $id = Uuid::uuid4()->toString();

        try {
            $session = $this->createStripeSession($id, $amount, $email, $description, $items, $extraAmount);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: Stripe session creation failed: ' . $e->getMessage());
            throw new HTTPException(
                'Could not create the payment session. Please try again.',
                Response::HTTP_BAD_GATEWAY
            );
        }

        // Stripe succeeded: safe to persist. Only whitelisted fields reach the DB.
        $this->repo->create([
            'reservation_id' => $reservationId,
            'customer_name'  => $name,
            'customer_email' => $email,
            'description'    => $description,
            'amount'         => $amount,
            'extra_amount'   => $extraAmount,
            'currency'       => $currency,
            'created_by'     => mb_substr($createdBy, 0, 255),
        ], $id);

        if ($items !== []) {
            $this->itemRepository()->replaceForLink($id, $items);
        }

        $expiresAt = isset($session->expires_at) && $session->expires_at
            ? date('Y-m-d H:i:s', (int) $session->expires_at)
            : null;

        $this->repo->attachSession($id, (string) $session->id, (string) $session->url, $expiresAt);

        $link = $this->repo->findById($id);

        // Best-effort: send the link email on creation. A send failure must not
        // fail the create call — the admin can resend from the list.
        try {
            $this->dispatchLinkEmail($link);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: initial email send failed: ' . $e->getMessage());
        }

        return $this->attachAccessUrl($this->repo->findById($id));
    }

    /**
     * Edit the single active additional payment. Paid/cancelled links are
     * immutable. The previous Stripe session is expired before a replacement
     * is generated, then the refreshed link is emailed automatically.
     */
    public function updateLink(string $id, array $data): object
    {
        $link = $this->getLink($id);
        if ($link->status !== 'pending') {
            throw new HTTPException('Only pending payment links can be edited', Response::HTTP_BAD_REQUEST);
        }

        [$items, $amount, $extraAmount] = $this->resolvePricing($data);
        $description = $this->assertValidDescription($data['description'] ?? null);
        $email       = $this->assertValidEmail($data['customer_email'] ?? null);
        $name        = $this->normalizeName($data['customer_name'] ?? null);

        $reservation = $this->reservationRepository->getById((string) $link->reservation_id);
        if (!$reservation || $reservation->status === 'cancelled' || empty($reservation->is_paid)) {
            throw new HTTPException('The reservation cannot receive a payment link', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->getStripeService()->expireCheckoutSession($link->stripe_session_id ?? null);
            $session = $this->createStripeSession((string) $link->id, $amount, $email, $description, $items, $extraAmount);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: replacement session failed: ' . $e->getMessage());
            throw new HTTPException('Could not replace the payment session. Please try again.', Response::HTTP_BAD_GATEWAY);
        }

        $this->repo->updateEditable($id, [
            'customer_name'  => $name,
            'customer_email' => $email,
            'description'    => $description,
            'amount'         => $amount,
            'extra_amount'   => $extraAmount,
            'currency'       => $this->normalizeCurrency($data['currency'] ?? $link->currency ?? null),
        ]);
        $this->itemRepository()->replaceForLink($id, $items);

        $expiresAt = isset($session->expires_at) && $session->expires_at
            ? date('Y-m-d H:i:s', (int) $session->expires_at)
            : null;
        $this->repo->attachSession($id, (string) $session->id, (string) $session->url, $expiresAt);

        $updated = $this->repo->findById($id);
        try {
            $this->dispatchLinkEmail($updated);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: updated email send failed: ' . $e->getMessage());
        }

        return $this->attachAccessUrl($this->repo->findById($id));
    }

    /**
     * Sin ítems: una sola línea con la descripción (comportamiento histórico).
     * Con ítems: una línea por ítem más el cargo extra, que suman exactamente $amount.
     */
    private function createStripeSession(
        string $linkId,
        float $amount,
        string $email,
        string $description,
        array $items = [],
        ?float $extraAmount = null,
        ?int $expiresInSeconds = null
    ): object {
        $metadata = ['type' => 'custom_payment_link', 'payment_link_id' => $linkId];

        if ($items === []) {
            return $this->getStripeService()->createCheckoutSession(
                $amount,
                $email,
                '',
                $description,
                0.0,
                $metadata,
                $expiresInSeconds
            );
        }

        return $this->getStripeService()->createItemizedCheckoutSession(
            $this->buildStripeLines($items, (float) ($extraAmount ?? 0), $description),
            $email,
            $metadata,
            $expiresInSeconds
        );
    }

    /** @return array<int, array{name:string, amount:float}> */
    private function buildStripeLines(array $items, float $extraAmount, string $description): array
    {
        $lines = [];
        foreach ($items as $item) {
            $label = self::ITEM_TYPES[$item['item_type']] ?? 'Item';
            $lines[] = [
                'name'   => $label . ': ' . $item['name'],
                'amount' => (float) $item['price'],
            ];
        }

        if ($extraAmount > 0) {
            $lines[] = ['name' => $description, 'amount' => $extraAmount];
        }

        return $lines;
    }

    /**
     * Monto del link. Sin ítems: monto manual (como siempre). Con ítems: suma de
     * los precios de los ítems + cargo extra opcional; el monto que mande el
     * cliente se ignora.
     *
     * @return array{0: array<int, array<string,mixed>>, 1: float, 2: ?float}
     */
    private function resolvePricing(array $data): array
    {
        $items = $this->resolveItems($data['items'] ?? []);

        if ($items === []) {
            return [[], $this->assertValidAmount($data['amount'] ?? null), null];
        }

        $rawExtra = $data['extra_amount'] ?? null;
        if ($rawExtra === null || $rawExtra === '') {
            $extra = 0.0;
        } elseif (is_bool($rawExtra) || is_array($rawExtra) || !is_numeric($rawExtra) || (float) $rawExtra < 0) {
            throw new HTTPException('Other charge must be a number greater than or equal to zero', Response::HTTP_BAD_REQUEST);
        } else {
            $extra = round((float) $rawExtra, 2);
        }

        $total = array_sum(array_column($items, 'price')) + $extra;

        return [$items, $this->assertValidAmount(round($total, 2)), $extra];
    }

    /**
     * Valida los ítems contra el catálogo (deben existir, estar activos y no
     * repetirse). Nombre/detalle/precio de catálogo salen de la BD; del request
     * solo se toma el precio cobrado en el link. Cantidad siempre 1.
     *
     * @param mixed $raw [{item_type, item_id, price}]
     * @return array<int, array<string,mixed>>
     */
    private function resolveItems($raw): array
    {
        if (!is_array($raw) || $raw === []) {
            return [];
        }

        $items = [];
        foreach ($raw as $entry) {
            $type = is_array($entry) ? (string) ($entry['item_type'] ?? '') : '';
            $itemId = is_array($entry) ? trim((string) ($entry['item_id'] ?? '')) : '';

            if (!isset(self::ITEM_TYPES[$type]) || $itemId === '') {
                throw new HTTPException('Invalid payment link item', Response::HTTP_BAD_REQUEST);
            }

            $key = $type . ':' . $itemId;
            if (isset($items[$key])) {
                throw new HTTPException('Each item can only be added once', Response::HTTP_BAD_REQUEST);
            }

            if ($type === 'addon') {
                $catalog = $this->addonRepository()->getById($itemId);
                $catalogPrice = $catalog ? (float) $catalog->base_price : 0.0;
                $detail = null;
            } else {
                $catalog = $this->customServiceRepository()->getById($itemId);
                $catalogPrice = $catalog ? (float) $catalog->price : 0.0;
                $detail = $catalog->detail ?? null;
            }

            if (!$catalog || !$catalog->is_active) {
                throw new HTTPException(self::ITEM_TYPES[$type] . ' not found or inactive', Response::HTTP_BAD_REQUEST);
            }

            $price = $entry['price'] ?? null;
            if ($price === null || $price === '' || is_bool($price) || is_array($price) || !is_numeric($price) || (float) $price < 0) {
                throw new HTTPException('Item price must be a number greater than or equal to zero', Response::HTTP_BAD_REQUEST);
            }

            $items[$key] = [
                'item_type'     => $type,
                'item_id'       => $itemId,
                'name'          => (string) $catalog->name,
                'detail'        => $detail,
                'catalog_price' => round($catalogPrice, 2),
                'price'         => round((float) $price, 2),
            ];
        }

        return array_values($items);
    }

    /**
     * Tabla HTML de ítems para el correo (vacía si el link no tiene ítems).
     */
    private function buildItemsTable(object $link): string
    {
        $items = $link->items ?? $this->itemRepository()->getByLink((string) $link->id);
        if (empty($items)) {
            return '';
        }

        $money = static fn ($v): string => '$' . number_format((float) $v, 2);
        $th = 'padding: 10px 12px; font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;';
        $td = 'padding: 12px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;';

        $rows = '';
        foreach ($items as $item) {
            $item = (array) $item;
            $label = self::ITEM_TYPES[$item['item_type'] ?? ''] ?? 'Item';
            $detail = trim((string) ($item['detail'] ?? ''));
            $rows .= '<tr>'
                . '<td style="' . $td . '"><strong>' . esc((string) $item['name']) . '</strong>'
                . '<br><span style="font-size: 12px; color: #6b7280;">' . esc($label) . ($detail !== '' ? ' · ' . esc($detail) : '') . '</span></td>'
                . '<td style="' . $td . ' text-align: center;">1</td>'
                . '<td style="' . $td . ' text-align: right;">' . esc($money($item['price'])) . '</td>'
                . '<td style="' . $td . ' text-align: right;">' . esc($money($item['price'])) . '</td>'
                . '</tr>';
        }

        $extra = (float) ($link->extra_amount ?? 0);
        if ($extra > 0) {
            $rows .= '<tr>'
                . '<td style="' . $td . '"><strong>Other charge</strong><br><span style="font-size: 12px; color: #6b7280;">' . esc((string) $link->description) . '</span></td>'
                . '<td style="' . $td . ' text-align: center;">1</td>'
                . '<td style="' . $td . ' text-align: right;">' . esc($money($extra)) . '</td>'
                . '<td style="' . $td . ' text-align: right;">' . esc($money($extra)) . '</td>'
                . '</tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; border-collapse: separate;">'
            . '<tr>'
            . '<td style="' . $th . '">Item</td>'
            . '<td style="' . $th . ' text-align: center;">Qty</td>'
            . '<td style="' . $th . ' text-align: right;">Unit Price</td>'
            . '<td style="' . $th . ' text-align: right;">Total</td>'
            . '</tr>'
            . $rows
            . '<tr>'
            . '<td colspan="3" style="padding: 12px; font-size: 14px; font-weight: 700; color: #1F2937; text-align: right; background-color: #FFF0F6;">Total</td>'
            . '<td style="padding: 12px; font-size: 14px; font-weight: 700; color: #FF74B7; text-align: right; background-color: #FFF0F6;">' . esc($money($link->amount)) . '</td>'
            . '</tr>'
            . '</table>';
    }

    // ---------------------------------------------------------------------
    // Email
    // ---------------------------------------------------------------------

    /**
     * Send / resend the payment link email. Public entry point for the
     * POST /payment-links/{id}/send-email endpoint.
     */
    public function sendLinkEmail(string $id): object
    {
        $link = $this->getLink($id);

        if ($link->status !== 'pending') {
            throw new HTTPException('Only pending payment links can be sent', Response::HTTP_BAD_REQUEST);
        }

        $this->dispatchLinkEmail($link);

        return $link;
    }

    /**
     * Render the `custom_payment_link` template and send it. Records the send in
     * the reservation timeline when the link is tied to a reservation
     * (acceptance criterion 9).
     *
     * @throws HTTPException 500 when the template is missing (clear message, not
     *                       an opaque error) or when the send itself throws.
     */
    private function dispatchLinkEmail(object $link): void
    {
        $recipient = (string) ($link->customer_email ?? '');
        if ($recipient === '') {
            throw new HTTPException('The payment link has no customer email', Response::HTTP_BAD_REQUEST);
        }

        $amountLabel = number_format((float) $link->amount, 2);

        // Saludo con SOLO el primer nombre, igual que el resto de las plantillas
        // (patrón `strtok(trim($fullName), ' ')` usado en ReservationService).
        $firstName = strtok(trim((string) ($link->customer_name ?? '')), ' ') ?: 'there';

        // XSS: description and customer_name are free admin text that lands in
        // the email HTML. EmailTemplateService::render() does a plain
        // str_replace, so escape here. payment_url is our own URL; encode it
        // for an attribute context.
        //
        // Probe with ensureLink() (non-destructive — reuses the current token,
        // or mints one without invalidating anything) so a broken/missing
        // template is caught BEFORE we touch the token. Renewing eagerly and
        // then failing to render would kill the customer's still-working link
        // for nothing.
        $vars = [
            'customer_name' => esc($firstName),
            'description'   => esc((string) $link->description),
            'amount'        => esc($amountLabel),
            'items_table'   => $this->buildItemsTable($link),
            'payment_url'   => esc($this->getAccessService()->ensureLink('custom_payment_link', (string) $link->id), 'attr'),
        ];

        // render() throws (via fallback()) when the slug is unknown and has no
        // PHP view. Turn that into a clear, actionable message instead of an
        // opaque 500.
        try {
            $rendered = $this->emailTemplateService->render(self::TEMPLATE_SLUG, $vars);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: template render failed: ' . $e->getMessage());
            throw new HTTPException(
                'The "custom_payment_link" email template is missing. Run CustomPaymentLinkEmailSeeder.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (trim((string) ($rendered['body'] ?? '')) === '') {
            throw new HTTPException(
                'The "custom_payment_link" email template is missing. Run CustomPaymentLinkEmailSeeder.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // The template renders fine — now it's safe to actually renew the
        // token (every real send, resend included, gets a fresh 6-day
        // window). Re-render with the freshly-renewed URL for the real send.
        $vars['payment_url'] = esc($this->getAccessService()->buildLink('custom_payment_link', (string) $link->id), 'attr');
        $rendered = $this->emailTemplateService->render(self::TEMPLATE_SLUG, $vars);

        try {
            $this->emailService->sendEmail($recipient, $rendered['subject'], $rendered['body']);
            $status = 'Sent';
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link email send failed: ' . $e->getMessage());
            $status = 'Failed';
        }

        if (!empty($link->reservation_id)) {
            $this->recordReservationTimeline(
                (string) $link->reservation_id,
                'Custom Payment Link Sent',
                'email',
                $recipient,
                (string) ($rendered['subject'] ?? 'Custom payment link'),
                (string) ($rendered['body'] ?? ''),
                $status
            );
        }

        if ($status === 'Failed') {
            throw new HTTPException('The payment link email could not be sent', Response::HTTP_BAD_GATEWAY);
        }
    }

    // ---------------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------------

    public function cancelLink(string $id): object
    {
        $link = $this->getLink($id);

        if ($link->status !== 'pending') {
            throw new HTTPException('Only pending payment links can be cancelled', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->getStripeService()->expireCheckoutSession($link->stripe_session_id ?? null);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: could not expire cancelled Stripe session: ' . $e->getMessage());
            throw new HTTPException('Could not cancel the active Stripe session', Response::HTTP_BAD_GATEWAY);
        }

        $this->repo->updateStatus($id, 'cancelled');

        return $this->getLink($id);
    }

    public function deleteLink(string $id): void
    {
        $this->getLink($id);
        throw new HTTPException(
            'Payment links are retained for audit history; cancel a pending link instead',
            Response::HTTP_BAD_REQUEST
        );
    }

    // ---------------------------------------------------------------------
    // Payment gateway (token redemption — see PaymentAccessService)
    // ---------------------------------------------------------------------

    /**
     * Create a fresh, short-lived Stripe Checkout Session for a still-pending
     * link. Called only from PaymentAccessService::redeem() when the customer
     * clicks the gateway link — recomputes nothing (the amount/description are
     * frozen at link-creation time by design, unlike a reservation's total),
     * but always mints a brand-new Stripe session so a stale/expired one never
     * blocks payment.
     *
     * @throws HTTPException 404 not found, 400 cancelled, 409 already paid.
     * @return array{session_id: string, payment_url: string}
     */
    public function regenerateSession(string $id, ?int $expiresInSeconds = null): array
    {
        $link = $this->repo->findById($id);

        if (!$link) {
            throw new HTTPException('Payment link not found', Response::HTTP_NOT_FOUND);
        }

        if ($link->status === 'paid') {
            throw new HTTPException('This payment link has already been paid', Response::HTTP_CONFLICT);
        }

        if ($link->status === 'cancelled') {
            throw new HTTPException('This payment link has been cancelled', Response::HTTP_BAD_REQUEST);
        }

        $reservation = $this->reservationRepository->getById((string) $link->reservation_id);
        if (!$reservation || $reservation->status === 'cancelled' || empty($reservation->is_paid)) {
            throw new HTTPException('The reservation can no longer receive this payment', Response::HTTP_BAD_REQUEST);
        }

        $session = $this->createStripeSession(
            $id,
            (float) $link->amount,
            (string) $link->customer_email,
            (string) $link->description,
            $this->itemRepository()->getByLink($id),
            $link->extra_amount !== null ? (float) $link->extra_amount : null,
            $expiresInSeconds
        );

        $expiresAt = isset($session->expires_at) && $session->expires_at
            ? date('Y-m-d H:i:s', (int) $session->expires_at)
            : null;

        $this->repo->attachSession($id, (string) $session->id, (string) $session->url, $expiresAt);

        return [
            'session_id'  => (string) $session->id,
            'payment_url' => (string) $session->url,
        ];
    }

    // ---------------------------------------------------------------------
    // Payment completion (webhook + verifyPayment)
    // ---------------------------------------------------------------------

    /**
     * Mark a custom payment link as paid from a completed Stripe Checkout
     * Session. Treats the session metadata as untrusted: the referenced link
     * must exist in our DB. Idempotent — a second delivery keeps the first
     * `paid_at` (acceptance criterion 7). Never touches a reservation
     * (acceptance criterion 4).
     *
     * @param object $session The Stripe Checkout Session object.
     * @return bool true when the link is (now or already) paid.
     */
    public function handlePaidSession(object $session): bool
    {
        $metadata = $session->metadata ?? null;
        $linkId   = null;
        if (is_object($metadata)) {
            $linkId = $metadata->payment_link_id ?? null;
        } elseif (is_array($metadata)) {
            $linkId = $metadata['payment_link_id'] ?? null;
        }

        if (!$linkId) {
            log_message('error', 'Custom payment link webhook: missing payment_link_id in metadata');
            return false;
        }

        $link = $this->repo->findById((string) $linkId);
        if (!$link) {
            log_message('error', 'Custom payment link webhook: unknown payment_link_id ' . $linkId);
            return false;
        }

        if ($link->status === 'paid') {
            log_message('info', 'Custom payment link ' . $linkId . ' already marked as paid');
            return true;
        }

        $paymentIntentId = (string) ($session->payment_intent ?? '');
        $won = $this->repo->markPaid((string) $linkId, $paymentIntentId, date('Y-m-d H:i:s'));
        if (!$won) {
            log_message('info', 'Custom payment link ' . $linkId . ' was already processed');
            return true;
        }

        $updated = $this->repo->findById((string) $linkId);

        if ($updated && !empty($updated->reservation_id)) {
            $this->recordReservationTimeline(
                (string) $updated->reservation_id,
                'Custom Payment Received',
                'payment',
                (string) ($updated->customer_email ?? ''),
                'Custom payment received',
                $this->buildPaymentReceivedBody($updated, $paymentIntentId),
                'Sent'
            );
        }

        return true;
    }

    // ---------------------------------------------------------------------
    // Validation helpers
    // ---------------------------------------------------------------------

    private function assertValidAmount($raw): float
    {
        if ($raw === null || $raw === '' || is_bool($raw) || is_array($raw) || !is_numeric($raw)) {
            throw new HTTPException('Amount must be a number', Response::HTTP_BAD_REQUEST);
        }

        $amount = round((float) $raw, 2);

        if ($amount < 0.01) {
            throw new HTTPException('Amount must be greater than 0', Response::HTTP_BAD_REQUEST);
        }

        if ($amount > self::MAX_AMOUNT) {
            throw new HTTPException(
                'Amount must not exceed $' . number_format(self::MAX_AMOUNT, 2),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $amount;
    }

    private function assertValidDescription($raw): string
    {
        if (!is_string($raw)) {
            throw new HTTPException('Description is required', Response::HTTP_BAD_REQUEST);
        }

        $description = trim($raw);

        if ($description === '') {
            throw new HTTPException('Description is required', Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($description) > 255) {
            throw new HTTPException('Description must be at most 255 characters', Response::HTTP_BAD_REQUEST);
        }

        return $description;
    }

    private function assertValidEmail($raw): string
    {
        if (!is_string($raw) || filter_var(trim($raw), FILTER_VALIDATE_EMAIL) === false) {
            throw new HTTPException('A valid customer email is required', Response::HTTP_BAD_REQUEST);
        }

        return strtolower(trim($raw));
    }

    private function assertValidReservationId($raw): string
    {
        if (!is_string($raw) || trim($raw) === '') {
            throw new HTTPException('Invalid reservation reference', Response::HTTP_BAD_REQUEST);
        }

        $reservation = $this->reservationRepository->getById($raw);
        if (!$reservation) {
            throw new HTTPException('The referenced reservation does not exist', Response::HTTP_NOT_FOUND);
        }

        return $raw;
    }

    private function normalizeName($raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $name = trim($raw);

        return $name === '' ? null : mb_substr($name, 0, 150);
    }

    private function normalizeCurrency($raw): string
    {
        if (!is_string($raw) || trim($raw) === '') {
            return 'usd';
        }

        return mb_substr(strtolower(trim($raw)), 0, 10);
    }

    // ---------------------------------------------------------------------
    // Timeline recording (B3 equivalent pattern)
    // ---------------------------------------------------------------------

    /**
     * Insert a row in reservation_email_history. Mirrors
     * ReservationService::recordSystemEmail() (which is private): template_id
     * null, sent_by System. Failures are swallowed — timeline bookkeeping must
     * never break a payment or an email send.
     */
    private function recordReservationTimeline(
        string $reservationId,
        string $templateName,
        string $eventType,
        string $recipient,
        string $subject,
        string $body,
        string $status
    ): void {
        try {
            $this->historyModel()->insert([
                'reservation_id'  => $reservationId,
                'template_id'     => null,
                'template_name'   => $templateName,
                'event_type'      => $eventType,
                'sent_by'         => 'System',
                'recipient_email' => $recipient !== '' ? $recipient : '—',
                'cc_emails'       => null,
                'email_subject'   => mb_substr($subject, 0, 255),
                'email_body'      => $body !== '' ? $body : '—',
                'status'          => in_array($status, ['Sent', 'Failed', 'Pending'], true) ? $status : 'Sent',
                'sent_at'         => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: failed to record reservation timeline: ' . $e->getMessage());
        }
    }

    private function buildPaymentReceivedBody(object $link, string $paymentIntentId): string
    {
        return '<div style="font-family: Arial, sans-serif; font-size: 14px; color: #1F2937;">'
            . '<p style="margin: 0 0 8px;"><strong>Custom payment received via Stripe.</strong></p>'
            . '<p style="margin: 0 0 4px;">Description: ' . esc((string) $link->description) . '</p>'
            . '<p style="margin: 0 0 4px;">Amount: $' . esc(number_format((float) $link->amount, 2)) . '</p>'
            . '<p style="margin: 0;">Payment intent: ' . esc($paymentIntentId !== '' ? $paymentIntentId : 'N/A') . '</p>'
            . '</div>';
    }
}
