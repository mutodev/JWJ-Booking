<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ReservationEmailDurationTest extends CIUnitTestCase
{
    private function durationRow(float $hours): string
    {
        $service = new ReservationService();
        $method = new \ReflectionMethod(ReservationService::class, 'buildDurationRow');
        $method->setAccessible(true);

        return $method->invoke($service, $hours);
    }

    private function reservation(float $durationHours): object
    {
        return (object) [
            'id' => 'test-reservation',
            'full_name' => 'Jamie Test',
            'email' => 'jamie@example.com',
            'service_name' => 'Mini Jam',
            'event_date' => '2026-08-15',
            'event_time' => '10:00',
            'event_address' => '123 Music Ave',
            'children_age_range' => '5-7',
            'children_count' => 12,
            'birthday_child_name' => 'Alex',
            'duration_hours' => $durationHours,
            'total_amount' => 250.00,
            'description' => 'Test reservation',
        ];
    }

    public function testDurationRowMatchesFrontendDurationFormat(): void
    {
        $this->assertStringContainsString('45 minutes', $this->durationRow(0.75));
        $this->assertStringContainsString('1 hour', $this->durationRow(1.0));
        $this->assertStringContainsString('1 hour 30 minutes', $this->durationRow(1.5));
    }

    public function testEmailFallbackViewsRenderFortyFiveMinuteDuration(): void
    {
        $reservation = $this->reservation(0.75);
        $totalDurationRow = $this->durationRow(0.75);

        $bodies = [
            view('emails/reservation_confirmation', [
                'reservation' => $reservation,
                'eventDate' => 'August 15, 2026',
                'totalAmount' => '250.00',
                'totalDurationRow' => $totalDurationRow,
            ]),
            view('emails/payment_notification', [
                'reservation' => $reservation,
                'confirmationUrl' => 'https://example.test/confirmation/test-reservation',
                'eventDate' => 'August 15, 2026',
                'totalAmount' => '250.00',
                'totalDurationRow' => $totalDurationRow,
            ]),
            view('emails/payment_confirmation', [
                'reservation' => $reservation,
                'eventDate' => 'August 15, 2026',
                'totalAmount' => '250.00',
                'totalDurationRow' => $totalDurationRow,
            ]),
        ];

        foreach ($bodies as $body) {
            $this->assertStringContainsString('45 minutes', $body);
            $this->assertStringNotContainsString('0.75 hours', $body);
            $this->assertStringNotContainsString('1 hour</td>', $body);
        }
    }
}
