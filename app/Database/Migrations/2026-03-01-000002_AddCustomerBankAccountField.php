<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerBankAccountField extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('bank_account', 'customers')) {
            $this->forge->addColumn('customers', [
                'bank_account' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'phone',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('bank_account', 'customers')) {
            $this->forge->dropColumn('customers', 'bank_account');
        }
    }
}
