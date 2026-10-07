<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Database\Seeder;

/**
 * Inserta {{items_table}} (tabla de add-ons / servicios personalizados del link)
 * en la plantilla `custom_payment_link`, justo después del párrafo de intro.
 * Se renderiza vacío cuando el link no tiene ítems.
 *
 * No reescribe el cuerpo guardado. Seguro de re-ejecutar: salta si el
 * placeholder ya existe, si no encuentra el ancla o si la plantilla fue
 * personalizada desde el panel.
 *
 * php spark db:seed AddItemsTableToCustomPaymentLinkEmailSeeder
 */
class AddItemsTableToCustomPaymentLinkEmailSeeder extends Seeder
{
    use EmailTemplateSeedGuard;

    private const SLUG = 'custom_payment_link';
    private const PLACEHOLDER = '{{items_table}}';
    private const ANCHOR = '{{content_intro}}</p>';

    public function run()
    {
        if ($this->templateIsCustomized(self::SLUG)) {
            echo self::SLUG . " was customized in the admin panel — skipping (add " . self::PLACEHOLDER . " manually).\n";
            return;
        }

        $row = $this->db->table('email_templates')
            ->select('id, body, available_variables')
            ->where('slug', self::SLUG)
            ->get()
            ->getRow();

        if (!$row) {
            echo self::SLUG . " template not found — run CustomPaymentLinkEmailSeeder first.\n";
            return;
        }

        if (strpos($row->body, self::PLACEHOLDER) !== false) {
            echo self::SLUG . " already has " . self::PLACEHOLDER . " — skipping.\n";
            return;
        }

        if (strpos($row->body, self::ANCHOR) === false) {
            echo self::SLUG . ": anchor not found — skipping instead of guessing where to insert.\n";
            return;
        }

        $update = [
            'body' => str_replace(
                self::ANCHOR,
                self::ANCHOR . "\n                            " . self::PLACEHOLDER,
                $row->body
            ),
        ];

        $variables = json_decode((string) ($row->available_variables ?? ''), true);
        if (is_array($variables) && !in_array('items_table', $variables, true)) {
            $variables[] = 'items_table';
            $update['available_variables'] = json_encode(array_values($variables));
        }

        $this->db->table('email_templates')->where('id', $row->id)->update($update);

        echo self::SLUG . ": " . self::PLACEHOLDER . " placeholder added.\n";
    }
}
