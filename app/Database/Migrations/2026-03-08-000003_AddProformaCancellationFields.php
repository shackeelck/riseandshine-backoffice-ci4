<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProformaCancellationFields extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('cancel_reason', 'proformas')) {
            $fields['cancel_reason'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('cancelled_by', 'proformas')) {
            $fields['cancelled_by'] = [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('cancelled_at', 'proformas')) {
            $fields['cancelled_at'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('proformas', $fields);
        }
    }

    public function down()
    {
        foreach (['cancel_reason', 'cancelled_by', 'cancelled_at'] as $field) {
            if ($this->db->fieldExists($field, 'proformas')) {
                $this->forge->dropColumn('proformas', $field);
            }
        }
    }
}
