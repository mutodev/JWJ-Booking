<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePromoCodesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true,
                'null' => false,
                'comment' => 'Código promocional único'
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Descripción del código promocional'
            ],
            'discount_type' => [
                'type' => 'ENUM',
                'constraint' => ['percentage', 'fixed_amount'],
                'null' => false,
                'comment' => 'Tipo de descuento: porcentaje o monto fijo'
            ],
            'discount_value' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'comment' => 'Valor del descuento (% o $)'
            ],
            'minimum_purchase' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => null,
                'comment' => 'Monto mínimo de compra requerido'
            ],
            'max_uses' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
                'comment' => 'Número máximo de usos (null = ilimitado)'
            ],
            'times_used' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => false,
                'comment' => 'Número de veces que se ha usado'
            ],
            'valid_from' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Fecha desde la cual es válido'
            ],
            'valid_until' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Fecha hasta la cual es válido'
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
                'null' => false,
                'comment' => 'Si el código está activo'
            ],
            'applies_to_travel_fee' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => false,
                'comment' => 'Si el descuento aplica al travel fee (generalmente NO)'
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => null,
                'on_update' => new RawSql('CURRENT_TIMESTAMP')
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('is_active');
        $this->forge->addKey('valid_from');
        $this->forge->addKey('valid_until');

        $this->forge->createTable('promo_codes');
    }

    public function down()
    {
        $this->forge->dropTable('promo_codes');
    }
}
