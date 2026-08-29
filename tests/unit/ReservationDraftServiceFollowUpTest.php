<?php

namespace Tests\Unit;

use App\Models\ReservationDraftModel;
use App\Services\BrevoEmailService;
use App\Services\EmailTemplateService;
use App\Services\ReservationDraftService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B4 — "Automatico de carritos abandonados semanal (7 dias)".
 *
 * Cubre ReservationDraftService::sendAbandonedFollowUps() (criterios 2, 5, 6, 7, 8)
 * y verifica que el contrato publico de sendFollowUpEmail() (accion manual del
 * admin) se conserva tras el refactor a dispatchFollowUp().
 *
 * Estrategia sin base de datos (regla del proyecto):
 *  - draftModel: subclase anonima de ReservationDraftModel con constructor anulado;
 *    getAbandonedForFollowUp()/find()/update() operan sobre un store en memoria y
 *    registran cada llamada.
 *  - templateService (EmailTemplateService) y emailService (BrevoEmailService):
 *    subclases anonimas con constructor anulado, inyectadas por ReflectionProperty
 *    en las propiedades nullable con getter lazy (patron historyModel()).
 *
 * @internal
 */
final class ReservationDraftServiceFollowUpTest extends CIUnitTestCase
{
    private ReservationDraftService $service;
    private object $model;
    private object $template;
    private object $email;
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new class extends ReservationDraftModel {
            /** @var array<string,object> */
            public array $store = [];
            /** @var array<int,object>|null */
            public ?array $forcedEligible = null;
            public ?int $lastDaysOld = null;
            /** @var array<int,array{id:mixed,data:mixed}> */
            public array $updates = [];
            /** @var array<int,string> ids cuyo follow_up_sent_at "no persiste" */
            public array $noPersist = [];

            public function __construct()
            {
            }

            public function getAbandonedForFollowUp(int $daysOld = 7): array
            {
                $this->lastDaysOld = $daysOld;

                if ($this->forcedEligible !== null) {
                    return $this->forcedEligible;
                }

                $out = [];
                foreach ($this->store as $d) {
                    if (empty($d->completed) && ! empty($d->email) && empty($d->follow_up_sent_at)) {
                        $out[] = $d;
                    }
                }
                usort($out, static fn ($a, $b) => strcmp((string) $a->last_activity_at, (string) $b->last_activity_at));

                return $out;
            }

            public function find($id = null)
            {
                return $this->store[$id] ?? null;
            }

            public function update($id = null, $data = null): bool
            {
                $this->updates[] = ['id' => $id, 'data' => $data];

                if (isset($this->store[$id]) && is_array($data)) {
                    foreach ($data as $k => $v) {
                        if ($k === 'follow_up_sent_at' && in_array($id, $this->noPersist, true)) {
                            continue; // simula fallo silencioso de persistencia
                        }
                        $this->store[$id]->{$k} = $v;
                    }
                }

                return true;
            }
        };

        $this->template = new class extends EmailTemplateService {
            /** @var array<int,array{0:string,1:array}> */
            public array $renders = [];
            /** @var callable|null */
            public $onRender = null;

            public function __construct()
            {
            }

            public function render(string $slug, array $variables): array
            {
                $this->renders[] = [$slug, $variables];
                if ($this->onRender !== null) {
                    ($this->onRender)($slug, $variables);
                }

                return ['subject' => 'Subject ' . $slug, 'body' => '<p>Body ' . $slug . '</p>'];
            }
        };

        $this->email = new class extends BrevoEmailService {
            /** @var array<int,array{0:string,1:string,2:string}> */
            public array $sent = [];
            /** @var array<int,string> destinatarios que lanzan excepcion */
            public array $failFor = [];
            /** @var array<int,string> destinatarios para los que sendEmail devuelve false */
            public array $returnFalseFor = [];

            public function __construct()
            {
            }

            public function sendEmail($to, $subject, $htmlContent, array $cc = [], array $bcc = [])
            {
                $this->sent[] = [$to, $subject, $htmlContent];

                if (in_array($to, $this->failFor, true)) {
                    throw new \RuntimeException('brevo unavailable');
                }
                if (in_array($to, $this->returnFalseFor, true)) {
                    return false;
                }

                return true;
            }
        };

        $this->service = new ReservationDraftService();
        $this->setProp('draftModel', $this->model);
        $this->setProp('templateService', $this->template);
        $this->setProp('emailService', $this->email);
    }

    private function setProp(string $name, $value): void
    {
        $ref = new \ReflectionProperty(ReservationDraftService::class, $name);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    private function draft(array $o = []): object
    {
        return (object) array_merge([
            'id'                => 'd' . (++$this->seq),
            'email'             => 'buyer' . $this->seq . '@example.com',
            'completed'         => 0,
            'follow_up_sent_at' => null,
            'form_data'         => ['full_name' => 'Jamie Buyer'],
            'last_activity_at'  => sprintf('2026-08-%02d 10:00:00', ($this->seq % 27) + 1),
            'reservation_id'    => null,
        ], $o);
    }

    private function addDraft(array $o = []): object
    {
        $d                          = $this->draft($o);
        $this->model->store[$d->id] = $d;

        return $d;
    }

    // -------------------------------------------------------------------------
    // sendAbandonedFollowUps() — criterio 7 (idempotente / lista vacia)
    // -------------------------------------------------------------------------

    public function testEmptyListReturnsZeroAndTouchesNothing(): void
    {
        $this->assertSame(0, $this->service->sendAbandonedFollowUps());
        $this->assertSame([], $this->model->updates);
        $this->assertSame([], $this->email->sent);
    }

    // -------------------------------------------------------------------------
    // criterio 2 / 8 — envio y marcado
    // -------------------------------------------------------------------------

    public function testAllEligibleDraftsAreSentAndMarked(): void
    {
        $this->addDraft();
        $this->addDraft();
        $this->addDraft();

        $sent = $this->service->sendAbandonedFollowUps();

        $this->assertSame(3, $sent);
        $this->assertCount(3, $this->email->sent);
        $this->assertCount(3, $this->model->updates);
        foreach ($this->model->updates as $u) {
            $this->assertArrayHasKey('follow_up_sent_at', $u['data']);
            $this->assertNotEmpty($u['data']['follow_up_sent_at']);
        }
    }

    public function testUsesAbandonedCartFollowupTemplateWithExpectedVariables(): void
    {
        $this->addDraft(['form_data' => ['full_name' => 'Alice Wong']]);

        $this->service->sendAbandonedFollowUps();

        $this->assertCount(1, $this->template->renders);
        [$slug, $vars] = $this->template->renders[0];
        $this->assertSame('abandoned_cart_followup', $slug);
        $this->assertSame(['customer_name', 'resume_url'], array_keys($vars));
        $this->assertSame('Alice Wong', $vars['customer_name']);
        $this->assertSame(base_url(), $vars['resume_url']);
    }

    public function testCustomerNameFallsBackToThereWhenFormDataHasNoName(): void
    {
        $this->addDraft(['form_data' => []]);

        $this->service->sendAbandonedFollowUps();

        $this->assertSame('there', $this->template->renders[0][1]['customer_name']);
    }

    public function testCustomerNameIsReadFromJsonStringFormData(): void
    {
        $this->addDraft(['form_data' => json_encode(['name' => 'Bob Stringly'])]);

        $this->service->sendAbandonedFollowUps();

        $this->assertSame('Bob Stringly', $this->template->renders[0][1]['customer_name']);
    }

    // -------------------------------------------------------------------------
    // criterio 5 — un fallo no aborta la corrida
    // -------------------------------------------------------------------------

    public function testFailedSendIsLoggedNotMarkedAndBatchContinues(): void
    {
        $ok1  = $this->addDraft();
        $bad  = $this->addDraft(['email' => 'bad@example.com']);
        $ok2  = $this->addDraft();
        $this->email->failFor = ['bad@example.com'];

        $sent = $this->service->sendAbandonedFollowUps();

        $this->assertSame(2, $sent);

        $markedIds = array_column($this->model->updates, 'id');
        $this->assertContains($ok1->id, $markedIds);
        $this->assertContains($ok2->id, $markedIds);
        $this->assertNotContains($bad->id, $markedIds, 'el draft que falla no debe marcarse');
        $this->assertNull($this->model->store[$bad->id]->follow_up_sent_at);

        $this->assertLogContains('error', "Abandoned cart follow-up failed for draft {$bad->id}");
    }

    public function testSendReturningFalseIsNotCountedAndNotMarked(): void
    {
        $this->addDraft();
        $rejected = $this->addDraft(['email' => 'reject@example.com']);
        $this->email->returnFalseFor = ['reject@example.com'];

        $sent = $this->service->sendAbandonedFollowUps();

        $this->assertSame(1, $sent);
        $this->assertNotContains($rejected->id, array_column($this->model->updates, 'id'));
    }

    // -------------------------------------------------------------------------
    // marca-luego-verifica: fallo de persistencia del timestamp
    // -------------------------------------------------------------------------

    public function testTimestampPersistenceFailureCountsAsFailureButDoesNotAbort(): void
    {
        $ok1 = $this->addDraft();
        $bad = $this->addDraft();
        $ok2 = $this->addDraft();
        $this->model->noPersist = [$bad->id];

        $sent = $this->service->sendAbandonedFollowUps();

        $this->assertSame(2, $sent, 'el draft cuyo timestamp no persiste no se cuenta');
        $this->assertLogContains('critical', "Abandoned cart follow-up was sent for draft {$bad->id}");
        $this->assertLogContains('error', "Abandoned cart follow-up failed for draft {$bad->id}");
        // el bucle siguio: los tres intentaron enviarse
        $this->assertCount(3, $this->email->sent);
    }

    // -------------------------------------------------------------------------
    // criterio 6 — nunca se reenvia
    // -------------------------------------------------------------------------

    public function testRunningTwiceInARowSendsZeroTheSecondTime(): void
    {
        $this->addDraft();
        $this->addDraft();

        $this->assertSame(2, $this->service->sendAbandonedFollowUps());
        $this->assertSame(0, $this->service->sendAbandonedFollowUps());
        $this->assertCount(2, $this->email->sent, 'no debe reenviar en la segunda corrida');
    }

    // -------------------------------------------------------------------------
    // DoS involuntario — cap de lote
    // -------------------------------------------------------------------------

    public function testBatchIsCappedAt200AndBacklogIsLogged(): void
    {
        for ($i = 0; $i < 205; $i++) {
            $this->addDraft();
        }

        $sent = $this->service->sendAbandonedFollowUps();

        $this->assertSame(200, $sent);
        $this->assertCount(200, $this->email->sent);
        $this->assertLogContains('info', '205 eligible drafts, processing 200 this run');

        // los 5 restantes siguen elegibles para la proxima corrida
        $this->assertSame(5, $this->service->sendAbandonedFollowUps());
    }

    // -------------------------------------------------------------------------
    // re-chequeo defensivo dentro del bucle
    // -------------------------------------------------------------------------

    public function testDefensiveRecheckSkipsIneligibleRowsInTheEligibleList(): void
    {
        $this->model->forcedEligible = [
            $this->draft(['completed' => 1]),
            $this->draft(['email' => '']),
            $this->draft(['follow_up_sent_at' => '2026-08-01 00:00:00']),
            $good = $this->draft(),
        ];
        // el "good" necesita estar en el store para find()/update()
        $this->model->store[$good->id] = $good;

        $sent = $this->service->sendAbandonedFollowUps();

        $this->assertSame(1, $sent);
        $this->assertSame([$good->id], array_column($this->model->updates, 'id'));
    }

    // -------------------------------------------------------------------------
    // normalizacion de $daysOld antes de consultar el modelo
    // -------------------------------------------------------------------------

    public function testDaysOldZeroIsNormalisedToSevenBeforeQuery(): void
    {
        $this->service->sendAbandonedFollowUps(0);
        $this->assertSame(7, $this->model->lastDaysOld);
    }

    public function testNegativeDaysOldIsNormalisedToSevenBeforeQuery(): void
    {
        $this->service->sendAbandonedFollowUps(-4);
        $this->assertSame(7, $this->model->lastDaysOld);
    }

    public function testValidDaysOldIsPassedThroughToTheModel(): void
    {
        $this->service->sendAbandonedFollowUps(14);
        $this->assertSame(14, $this->model->lastDaysOld);
    }

    // -------------------------------------------------------------------------
    // Contrato preservado de sendFollowUpEmail() (accion manual del admin)
    // -------------------------------------------------------------------------

    public function testSendFollowUpEmailReturnsRefreshedDraftOnSuccess(): void
    {
        $d = $this->addDraft();

        $result = $this->service->sendFollowUpEmail($d->id);

        $this->assertIsObject($result);
        $this->assertSame($d->id, $result->id);
        $this->assertNotEmpty($result->follow_up_sent_at);
        $this->assertCount(1, $this->email->sent);
    }

    public function testSendFollowUpEmailReturnsFalseWhenDraftNotFound(): void
    {
        $this->assertFalse($this->service->sendFollowUpEmail('missing'));
        $this->assertSame([], $this->email->sent);
    }

    public function testSendFollowUpEmailReturnsFalseWhenDraftIsCompleted(): void
    {
        $d = $this->addDraft(['completed' => 1]);

        $this->assertFalse($this->service->sendFollowUpEmail($d->id));
        $this->assertSame([], $this->email->sent);
    }

    public function testSendFollowUpEmailReturnsFalseWhenDraftHasNoEmail(): void
    {
        $d = $this->addDraft(['email' => '']);

        $this->assertFalse($this->service->sendFollowUpEmail($d->id));
        $this->assertSame([], $this->email->sent);
    }

    public function testSendFollowUpEmailReturnsFalseWhenBrevoRejectsTheSend(): void
    {
        $d = $this->addDraft(['email' => 'reject@example.com']);
        $this->email->returnFalseFor = ['reject@example.com'];

        $this->assertFalse($this->service->sendFollowUpEmail($d->id));
        $this->assertSame([], $this->model->updates, 'no debe marcarse si Brevo rechaza');
    }

    public function testSendFollowUpEmailThrowsRuntimeExceptionWhenTimestampNotPersisted(): void
    {
        $d = $this->addDraft();
        $this->model->noPersist = [$d->id];

        $this->expectException(\RuntimeException::class);
        $this->service->sendFollowUpEmail($d->id);
    }

    public function testSendFollowUpEmailStillSendsWhenDraftWasAlreadyContacted(): void
    {
        // El metodo manual NO filtra follow_up_sent_at: el admin puede reenviar.
        // Contrato historico, se documenta explicitamente.
        $d = $this->addDraft(['follow_up_sent_at' => '2026-07-01 00:00:00']);

        $result = $this->service->sendFollowUpEmail($d->id);

        $this->assertIsObject($result);
        $this->assertCount(1, $this->email->sent);
    }
}
