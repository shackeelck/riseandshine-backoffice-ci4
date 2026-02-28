<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'id';

    protected $allowedFields = [
       'reference', 'customer_id','customer_ref_no', 'room_type_id','room_inventory_id', 'check_in', 'check_out', 'guests', 'status','overbook','arrival_flight', 'departure_flight','special_request','created_by','booked_by'
       'cancel_reason', 'cancelled_by', 'cancelled_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
