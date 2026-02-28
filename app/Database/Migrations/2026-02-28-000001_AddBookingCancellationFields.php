<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBookingCancellationFields extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('cancel_reason', 'bookings')) {
            $fields['cancel_reason'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'status',
            ];
        }

        if (! $this->db->fieldExists('cancelled_by', 'bookings')) {
            $fields['cancelled_by'] = [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'cancel_reason',
            ];
        }

        if (! $this->db->fieldExists('cancelled_at', 'bookings')) {
            $fields['cancelled_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'cancelled_by',
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('bookings', $fields);
        }
    }

    public function down()
    {
        foreach (['cancel_reason', 'cancelled_by', 'cancelled_at'] as $field) {
            if ($this->db->fieldExists($field, 'bookings')) {
                $this->forge->dropColumn('bookings', $field);
            }
        }
    }
}
