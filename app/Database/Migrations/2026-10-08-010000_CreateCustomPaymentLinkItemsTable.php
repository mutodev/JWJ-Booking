<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ítems opcionales (add-ons / servicios personalizados) de un link de pago
 * personalizado. name/detail/catalog_price son snapshot del catálogo; price es
 * lo cobrado en el link. Solo describen el cobro: nunca se agregan a la reserva.
 */
class CreateCustomPaymentLinkItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'CHAR', 'constraint' => 36],
            'payment_link_id' => ['type' => 'CHAR', 'constraint' => 36],
            'item_type'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'item_id'         => ['type' => 'CHAR', 'constraint' => 36],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'detail'          => ['type' => 'TEXT', 'null' => true],
            'catalog_price'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'price'           => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'quantity'        => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'sort_order'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_link_id');
        $this->forge->addForeignKey('payment_link_id', 'custom_payment_links', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('custom_payment_link_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('custom_payment_link_items', true);
    }
}
