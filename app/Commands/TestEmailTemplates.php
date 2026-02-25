<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\UserService;
use App\Services\LoginService;
use App\Services\ReservationService;
use App\Repositories\UserRepository;

class TestEmailTemplates extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:emails';
    protected $description = 'Tests all 4 email flows end-to-end (creates temp user, uses real reservation)';
    protected $usage       = 'test:emails <email> [reservation_id]';
    protected $arguments   = [
        'email'          => 'The email address to send test emails to',
        'reservation_id' => '(Optional) A real reservation ID for payment/confirmation tests',
    ];

    public function run(array $params)
    {
        $testEmail = $params[0] ?? null;
        $reservationId = $params[1] ?? null;

        if (!$testEmail) {
            CLI::error('Usage: php spark test:emails your@email.com [reservation_id]');
            return;
        }

        // If no reservation provided, find the latest one
        if (!$reservationId) {
            $db = \Config\Database::connect();
            $row = $db->table('reservations')
                ->where('deleted_at', null)
                ->orderBy('created_at', 'DESC')
                ->limit(1)
                ->get()
                ->getRow();
            $reservationId = $row ? $row->id : null;
        }

        CLI::write('=============================================', 'yellow');
        CLI::write('  EMAIL FLOW INTEGRATION TEST', 'yellow');
        CLI::write('=============================================', 'yellow');
        CLI::write("Target email: {$testEmail}", 'light_gray');
        CLI::write("Reservation:  " . ($reservationId ?: 'NONE'), 'light_gray');
        CLI::newLine();

        // ============================================================
        // FLOW 1: Welcome Email (UserService::create)
        // ============================================================
        CLI::write('[1/4] WELCOME - UserService::create()', 'cyan');
        CLI::write('      Creating temp user → sends welcome email with password', 'light_gray');
        $tempUserId = null;
        try {
            $userService = new UserService();

            // Hard-delete any existing user with this email (including soft-deleted)
            $db2 = \Config\Database::connect();
            $db2->table('users')->where('email', $testEmail)->delete();
            $userRepo = new UserRepository();

            $roleId = 'c3d4e5f6-g7h8-9012-cdef-345678901234'; // Visualizador (least permissions)
            $user = $userService->create([
                'first_name' => 'Test',
                'last_name'  => 'EmailFlow',
                'email'      => $testEmail,
                'role_id'    => $roleId,
                'is_active'  => true,
            ]);
            $tempUserId = $user->id ?? null;
            CLI::write('      OK - User created, welcome email sent via real flow', 'green');
        } catch (\Throwable $e) {
            CLI::error('      FAIL: ' . $e->getMessage());
        }

        // ============================================================
        // FLOW 2: Reset Password (LoginService::resetPassword)
        // ============================================================
        CLI::write('[2/4] RESET PASSWORD - LoginService::resetPassword()', 'cyan');
        CLI::write('      Resetting password for test user → sends reset email', 'light_gray');
        try {
            $loginService = new LoginService();
            $loginService->resetPassword(['email' => $testEmail]);
            CLI::write('      OK - Password reset email sent via real flow', 'green');
        } catch (\Throwable $e) {
            CLI::error('      FAIL: ' . $e->getMessage());
        }

        // ============================================================
        // FLOW 3: Reservation Confirmation (ReservationService::sendConfirmationEmail)
        // ============================================================
        CLI::write('[3/4] RESERVATION CONFIRMATION - ReservationService::sendConfirmationEmail()', 'cyan');
        if ($reservationId) {
            CLI::write("      Using reservation: {$reservationId}", 'light_gray');
            try {
                $reservationService = new ReservationService();
                $reservation = $reservationService->getById($reservationId);

                // Temporarily override email to test address
                $originalEmail = $reservation->email;
                $reservation->email = $testEmail;

                $reservationService->sendConfirmationEmail($reservation);
                CLI::write('      OK - Confirmation email sent via real flow', 'green');
            } catch (\Throwable $e) {
                CLI::error('      FAIL: ' . $e->getMessage());
            }
        } else {
            CLI::write('      SKIPPED - No reservation found in DB', 'yellow');
        }

        // ============================================================
        // FLOW 4: Payment Notification (ReservationService::sendPaymentEmail)
        // ============================================================
        CLI::write('[4/4] PAYMENT NOTIFICATION - ReservationService::sendPaymentEmail()', 'cyan');
        if ($reservationId) {
            CLI::write("      Using reservation: {$reservationId}", 'light_gray');
            CLI::write('      Creates Stripe Checkout Session (test mode) + sends payment email', 'light_gray');
            try {
                // Temporarily update reservation email to test address
                $db = \Config\Database::connect();
                $reservation = $db->table('reservations')
                    ->select('reservations.*, customers.email, customers.full_name')
                    ->join('customers', 'customers.id = reservations.customer_id', 'left')
                    ->where('reservations.id', $reservationId)
                    ->get()
                    ->getRow();

                $originalCustomerEmail = $reservation->email ?? null;

                // Update customer email temporarily for the test
                if ($reservation && $reservation->customer_id) {
                    $db->table('customers')
                        ->where('id', $reservation->customer_id)
                        ->update(['email' => $testEmail]);
                }

                $reservationService = new ReservationService();
                $result = $reservationService->sendPaymentEmail($reservationId);
                CLI::write('      OK - Payment email sent via real flow (Stripe session: ' . $result['session_id'] . ')', 'green');

                // Restore original customer email
                if ($reservation && $reservation->customer_id && $originalCustomerEmail) {
                    $db->table('customers')
                        ->where('id', $reservation->customer_id)
                        ->update(['email' => $originalCustomerEmail]);
                }
            } catch (\Throwable $e) {
                CLI::error('      FAIL: ' . $e->getMessage());
                // Restore email even on failure
                if (isset($reservation, $originalCustomerEmail) && $reservation->customer_id) {
                    $db = \Config\Database::connect();
                    $db->table('customers')
                        ->where('id', $reservation->customer_id)
                        ->update(['email' => $originalCustomerEmail]);
                }
            }
        } else {
            CLI::write('      SKIPPED - No reservation found in DB', 'yellow');
        }

        // ============================================================
        // CLEANUP: Delete temp user
        // ============================================================
        if ($tempUserId) {
            try {
                $db3 = \Config\Database::connect();
                $db3->table('users')->where('id', $tempUserId)->delete();
                CLI::write('      (Temp test user hard-deleted)', 'light_gray');
            } catch (\Throwable $e) {
                CLI::write('      Warning: Could not clean up test user: ' . $e->getMessage(), 'yellow');
            }
        }

        CLI::newLine();
        CLI::write('=============================================', 'yellow');
        CLI::write("  DONE! Check inbox: {$testEmail}", 'yellow');
        CLI::write('  You should receive 4 emails:', 'yellow');
        CLI::write('  1. Welcome (with temp password)', 'light_gray');
        CLI::write('  2. Password Reset (with new password)', 'light_gray');
        CLI::write('  3. Reservation Confirmation', 'light_gray');
        CLI::write('  4. Payment Notification (with Stripe link)', 'light_gray');
        CLI::write('=============================================', 'yellow');
    }
}
