<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Inserta {{items_table}} (tabla de add-ons / servicios personalizados del link)
 * en la plantilla `custom_payment_link`. Se renderiza vacío cuando el link no
 * tiene ítems.
 *
 * La plantilla fue editada en producción, así que este seeder es ADITIVO:
 * aplica también a plantillas personalizadas, pero solo inserta el placeholder
 * (y agrega la variable a available_variables). No reescribe el cuerpo ni toca
 * subject, content, is_customized ni ninguna otra columna.
 *
 * Seguro de re-ejecutar: salta si el placeholder ya existe y, si no encuentra
 * ningún punto de inserción conocido, no modifica nada.
 *
 * php spark db:seed AddItemsTableToCustomPaymentLinkEmailSeeder
 */
class AddItemsTableToCustomPaymentLinkEmailSeeder extends Seeder
{
    private const SLUG = 'custom_payment_link';
    public const PLACEHOLDER = '{{items_table}}';

    public function run()
    {
        $row = $this->db->table('email_templates')
            ->select('id, body, available_variables')
            ->where('slug', self::SLUG)
            ->get()
            ->getRow();

        if (!$row) {
            echo self::SLUG . " template not found — run CustomPaymentLinkEmailSeeder first.\n";
            return;
        }

        if (strpos((string) $row->body, self::PLACEHOLDER) !== false) {
            echo self::SLUG . " already has " . self::PLACEHOLDER . " — skipping.\n";
            return;
        }

        $newBody = self::insertPlaceholder((string) $row->body);
        if ($newBody === null) {
            echo self::SLUG . ": no known insertion point found — nothing changed. Add " . self::PLACEHOLDER . " manually from the admin panel.\n";
            return;
        }

        $update = ['body' => $newBody];

        $variables = json_decode((string) ($row->available_variables ?? ''), true);
        if (is_array($variables) && !in_array('items_table', $variables, true)) {
            $variables[] = 'items_table';
            $update['available_variables'] = json_encode(array_values($variables));
        }

        $this->db->table('email_templates')->where('id', $row->id)->update($update);

        echo self::SLUG . ": " . self::PLACEHOLDER . " placeholder added (existing content untouched).\n";
    }

    /**
     * Devuelve el cuerpo con el placeholder insertado, o null si no hay un punto
     * de inserción reconocible. Solo agrega texto: el resto del cuerpo queda
     * byte a byte igual.
     *
     * Orden de preferencia:
     *  1. justo después del párrafo de intro (`{{content_intro}}</p>`);
     *  2. justo antes de la tabla que contiene `{{description}}`;
     *  3. justo antes de la tabla que contiene `{{amount}}`;
     *  4. justo antes de la tabla del botón `{{payment_url}}`.
     */
    public static function insertPlaceholder(string $body): ?string
    {
        if (strpos($body, self::PLACEHOLDER) !== false) {
            return null;
        }

        $intro = '{{content_intro}}</p>';
        $pos = strpos($body, $intro);
        if ($pos !== false) {
            $at = $pos + strlen($intro);
            return substr($body, 0, $at) . "\n" . self::PLACEHOLDER . substr($body, $at);
        }

        foreach (['{{description}}', '{{amount}}', '{{payment_url}}'] as $marker) {
            $markerPos = strpos($body, $marker);
            if ($markerPos === false) {
                continue;
            }

            $tablePos = strripos(substr($body, 0, $markerPos), '<table');
            if ($tablePos === false) {
                continue;
            }

            return substr($body, 0, $tablePos) . self::PLACEHOLDER . "\n" . substr($body, $tablePos);
        }

        return null;
    }
}
