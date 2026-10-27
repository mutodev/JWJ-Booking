<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class MarketingTracking extends BaseConfig
{
    public bool $enabled = false;
    public string $allowedHost = 'bookingjamwithjamie.cloud';
    public string $googleTagId = '';
    public string $googleReservationSendTo = '';
    public string $googlePaymentSendTo = '';
    public string $metaPixelId = '';
    public string $openAiPixelId = '';

    public function __construct()
    {
        parent::__construct();

        $this->enabled = filter_var(env('marketing.enabled', false), FILTER_VALIDATE_BOOL);
        $this->allowedHost = strtolower(trim((string) env('marketing.allowedHost', 'bookingjamwithjamie.cloud')));
        $this->googleTagId = trim((string) env('marketing.googleTagId', ''));
        $this->googleReservationSendTo = trim((string) env('marketing.googleReservationSendTo', ''));
        $this->googlePaymentSendTo = trim((string) env('marketing.googlePaymentSendTo', ''));
        $this->metaPixelId = trim((string) env('marketing.metaPixelId', ''));
        $this->openAiPixelId = trim((string) env('marketing.openAiPixelId', ''));
    }
}
