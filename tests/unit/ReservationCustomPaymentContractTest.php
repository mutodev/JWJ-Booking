<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

final class ReservationCustomPaymentContractTest extends CIUnitTestCase
{
    private function read(string $path): string
    {
        return (string) file_get_contents(ROOTPATH . $path);
    }

    public function testReservationTableKeepsOriginalAndAddsPersonalizedPaymentButtons(): void
    {
        $vue = $this->read('frontend/src/components/admin/reservations/Reservations.vue');

        $this->assertStringContainsString('title="Send payment link"', $vue);
        $this->assertStringContainsString('title="Manage additional payment"', $vue);
        $this->assertStringContainsString('custom_payment_paid', $vue);
        $this->assertStringContainsString('combined_total', $vue);
        $this->assertStringContainsString('#expand="item"', $vue);
        $this->assertStringNotContainsString('{ text: "Event Type", value: "event_type" }', $vue);
    }

    public function testPersonalizedModalUsesReservationAndSupportsResend(): void
    {
        $vue = $this->read('frontend/src/components/admin/reservations/ReservationCustomPaymentModal.vue');

        $this->assertStringContainsString('reservation_id: props.reservation.id', $vue);
        $this->assertStringContainsString('/payment-links/${pending.value.id}/send-email', $vue);
        $this->assertStringContainsString('Additional payment', $vue);
        $this->assertStringContainsString('api.put(`/payment-links/${pending.value.id}`', $vue);
        $this->assertStringContainsString('/cancel`', $vue);
        $this->assertStringContainsString('if (!isEditing) close();', $vue);
    }

    public function testStandalonePaymentLinksScreenIsNotRouted(): void
    {
        $router = $this->read('frontend/src/router/index.js');
        $this->assertStringNotContainsString('payment-links", component: PaymentLinks', $router);
    }
}
