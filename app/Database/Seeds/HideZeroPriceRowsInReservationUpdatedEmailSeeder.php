<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Permite ocultar en el correo `reservation_updated` las filas "Base Service"
 * ({{base_price}}) y "Add-ons" ({{addons_total}}) cuando su valor es 0.
 *
 * La plantilla fue editada en producción, así que este seeder es ADITIVO: no
 * reemplaza las filas, solo las envuelve con marcadores:
 *
 *   {{base_price_row_start}}<tr>… ${{base_price}} …</tr>{{base_price_row_end}}
 *
 * ReservationService::sendReservationUpdatedEmail() rellena los marcadores con
 * `<!--` / `-->` cuando el valor es 0 (la fila queda comentada) y con '' en caso
 * contrario, así la fila conserva exactamente el HTML personalizado. No toca
 * subject, content, is_customized ni ninguna otra columna o plantilla.
 *
 * Seguro de re-ejecutar: salta las filas que ya tienen marcadores y, si no
 * encuentra la fila, no modifica nada.
 *
 * php spark db:seed HideZeroPriceRowsInReservationUpdatedEmailSeeder
 */
class HideZeroPriceRowsInReservationUpdatedEmailSeeder extends Seeder
{
    private const SLUG = 'reservation_updated';

    /** Placeholder del valor => prefijo de los marcadores que envuelven su fila. */
    public const ROWS = [
        'base_price'   => 'base_price_row',
        'addons_total' => 'addons_total_row',
    ];

    public function run()
    {
        $row = $this->db->table('email_templates')
            ->select('id, body')
            ->where('slug', self::SLUG)
            ->get()
            ->getRow();

        if (!$row) {
            echo self::SLUG . " template not found — nothing to do.\n";
            return;
        }

        $body = (string) $row->body;
        $changed = false;

        foreach (self::ROWS as $valueKey => $markerKey) {
            $newBody = self::wrapRow($body, $valueKey, $markerKey);
            if ($newBody === null) {
                echo self::SLUG . ": {{{$valueKey}}} row already wrapped or not found — skipping.\n";
                continue;
            }
            $body = $newBody;
            $changed = true;
            echo self::SLUG . ": {{{$valueKey}}} row wrapped with {{{$markerKey}_start}} / {{{$markerKey}_end}}.\n";
        }

        if ($changed) {
            $this->db->table('email_templates')->where('id', $row->id)->update(['body' => $body]);
        }
    }

    /**
     * Envuelve la primera fila <tr>…</tr> que contiene {{$valueKey}} con los
     * marcadores {{$markerKey_start}} / {{$markerKey_end}}. Devuelve null si ya
     * está envuelta o si no hay una fila que contenga el placeholder. El resto
     * del cuerpo queda byte a byte igual.
     */
    public static function wrapRow(string $body, string $valueKey, string $markerKey): ?string
    {
        $start = '{{' . $markerKey . '_start}}';
        $end   = '{{' . $markerKey . '_end}}';

        if (strpos($body, $start) !== false) {
            return null;
        }

        // <tr> más interno que contiene el placeholder (sin cruzar otro <tr> ni </tr>).
        $pattern = '~<tr\b(?:(?!<tr\b|</tr>).)*?' . preg_quote('{{' . $valueKey . '}}', '~') . '(?:(?!<tr\b|</tr>).)*?</tr>~is';
        if (!preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        [$rowHtml, $offset] = $match[0];

        return substr($body, 0, $offset) . $start . $rowHtml . $end . substr($body, $offset + strlen($rowHtml));
    }
}
