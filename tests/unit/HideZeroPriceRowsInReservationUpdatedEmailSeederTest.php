<?php

namespace Tests\Unit;

use App\Database\Seeds\HideZeroPriceRowsInReservationUpdatedEmailSeeder as Seeder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * El seeder solo envuelve la fila que contiene el placeholder; el resto de la
 * plantilla (personalizada en producción) queda intacto.
 *
 * @internal
 */
final class HideZeroPriceRowsInReservationUpdatedEmailSeederTest extends CIUnitTestCase
{
    private const BODY = <<<'HTML'
        <table>
            <tr>
                <td style="color: red;">Event Date</td>
                <td>{{event_date}}</td>
            </tr>
        </table>
        <table>
            <tr>
                <td style="custom: 1;">Jam (custom label)</td>
                <td>${{base_price}}</td>
            </tr>
            <tr>
                <td>Add-ons</td>
                <td>${{addons_total}}</td>
            </tr>
            {{discount_row}}
        </table>
        HTML;

    public function testWrapsOnlyTheRowContainingThePlaceholder(): void
    {
        $out = Seeder::wrapRow(self::BODY, 'base_price', 'base_price_row');

        $expectedRow = "<tr>\n        <td style=\"custom: 1;\">Jam (custom label)</td>\n        <td>\${{base_price}}</td>\n    </tr>";
        $this->assertStringContainsString('{{base_price_row_start}}' . $expectedRow . '{{base_price_row_end}}', $out);
        // Nada más cambia.
        $this->assertSame(self::BODY, str_replace(['{{base_price_row_start}}', '{{base_price_row_end}}'], '', $out));
    }

    public function testWrapsBothRowsIndependently(): void
    {
        $out = Seeder::wrapRow(self::BODY, 'base_price', 'base_price_row');
        $out = Seeder::wrapRow($out, 'addons_total', 'addons_total_row');

        $this->assertMatchesRegularExpression('~\{\{addons_total_row_start\}\}<tr>\s*<td>Add-ons</td>\s*<td>\$\{\{addons_total\}\}</td>\s*</tr>\{\{addons_total_row_end\}\}~', $out);
        $this->assertStringContainsString('{{base_price_row_end}}', $out);
        $this->assertStringContainsString('{{discount_row}}', $out);
    }

    public function testIsIdempotent(): void
    {
        $once = Seeder::wrapRow(self::BODY, 'base_price', 'base_price_row');

        $this->assertNull(Seeder::wrapRow($once, 'base_price', 'base_price_row'));
    }

    public function testReturnsNullWhenPlaceholderMissing(): void
    {
        $this->assertNull(Seeder::wrapRow('<table><tr><td>Hi</td></tr></table>', 'base_price', 'base_price_row'));
    }

    public function testDoesNotMatchLongerPlaceholderNames(): void
    {
        $body = '<table><tr><td>{{base_price_extra}}</td></tr><tr><td>${{base_price}}</td></tr></table>';
        $out = Seeder::wrapRow($body, 'base_price', 'base_price_row');

        $this->assertStringContainsString('{{base_price_row_start}}<tr><td>${{base_price}}</td></tr>{{base_price_row_end}}', $out);
        $this->assertStringStartsWith('<table><tr><td>{{base_price_extra}}</td></tr>', $out);
    }
}
