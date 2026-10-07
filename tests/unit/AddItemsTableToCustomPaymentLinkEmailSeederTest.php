<?php

namespace Tests\Unit;

use App\Database\Seeds\AddItemsTableToCustomPaymentLinkEmailSeeder as Seeder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * The seeder must only ADD {{items_table}} and leave every existing byte of a
 * (possibly production-customized) template untouched.
 *
 * @internal
 */
final class AddItemsTableToCustomPaymentLinkEmailSeederTest extends CIUnitTestCase
{
    private function assertOnlyPlaceholderAdded(string $before, ?string $after): void
    {
        $this->assertNotNull($after);
        $this->assertSame(1, substr_count($after, Seeder::PLACEHOLDER));
        $this->assertSame($before, str_replace([Seeder::PLACEHOLDER . "\n", "\n" . Seeder::PLACEHOLDER], '', $after));
    }

    public function testInsertsAfterIntroParagraph(): void
    {
        $body = '<h1>{{content_title}}</h1><p style="x">{{content_intro}}</p><table><tr><td>{{description}}</td></tr></table>';

        $after = Seeder::insertPlaceholder($body);

        $this->assertOnlyPlaceholderAdded($body, $after);
        $this->assertStringContainsString("{{content_intro}}</p>\n{{items_table}}<table>", $after);
    }

    public function testCustomizedTemplateWithoutIntroFallsBackToDescriptionTable(): void
    {
        $body = '<p>Hello, custom text written in production</p>'
            . '<table role="presentation"><tr><td>Info</td></tr></table>'
            . '<table role="presentation" style="a"><tr><td>{{description}}</td><td>${{amount}}</td></tr></table>';

        $after = Seeder::insertPlaceholder($body);

        $this->assertOnlyPlaceholderAdded($body, $after);
        $this->assertStringContainsString('</table>{{items_table}}' . "\n" . '<table role="presentation" style="a">', $after);
    }

    public function testFallsBackToAmountThenPaymentButton(): void
    {
        $amountOnly = '<p>Hi</p><TABLE><tr><td>${{amount}}</td></tr></TABLE>';
        $this->assertOnlyPlaceholderAdded($amountOnly, Seeder::insertPlaceholder($amountOnly));

        $buttonOnly = '<p>Hi</p><table><tr><td><a href="{{payment_url}}">Pay</a></td></tr></table>';
        $after = Seeder::insertPlaceholder($buttonOnly);
        $this->assertOnlyPlaceholderAdded($buttonOnly, $after);
        $this->assertStringStartsWith('<p>Hi</p>{{items_table}}', $after);
    }

    public function testReturnsNullWhenNoInsertionPointExists(): void
    {
        $this->assertNull(Seeder::insertPlaceholder('<p>Plain text with {{payment_url}} but no table</p>'));
    }

    public function testDoesNothingWhenPlaceholderAlreadyPresent(): void
    {
        $this->assertNull(Seeder::insertPlaceholder('<p>{{content_intro}}</p>{{items_table}}'));
    }

    public function testWorksOnTheSeededDefaultBody(): void
    {
        $src = (string) file_get_contents(APPPATH . 'Database/Seeds/CustomPaymentLinkEmailSeeder.php');
        // The default seeder body already ships with the placeholder; strip it to
        // simulate the template as it exists in production today.
        $legacy = str_replace("                            {{items_table}}\r\n", '', str_replace("                            {{items_table}}\n", '', $src));

        $after = Seeder::insertPlaceholder($legacy);

        $this->assertNotNull($after);
        $this->assertSame(1, substr_count($after, Seeder::PLACEHOLDER));
    }
}
