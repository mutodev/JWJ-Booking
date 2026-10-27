<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\MarketingTracking;

class MarketingTrackingConfigTest extends CIUnitTestCase
{
    public function testTrackingRequiresMasterSwitchAndExactHost(): void
    {
        $config = new MarketingTracking();
        $config->enabled = true;
        $config->allowedHost = 'bookingjamwithjamie.cloud';

        $this->assertTrue($config->isEnabledForHost('bookingjamwithjamie.cloud'));
        $this->assertTrue($config->isEnabledForHost('BOOKINGJAMWITHJAMIE.CLOUD.'));
        $this->assertFalse($config->isEnabledForHost('www.bookingjamwithjamie.cloud'));
        $this->assertFalse($config->isEnabledForHost('bookingjamwithjamie.cloud.example.com'));

        $config->enabled = false;
        $this->assertFalse($config->isEnabledForHost('bookingjamwithjamie.cloud'));
    }
}
