<?php

namespace App\Models;
use CodeIgniter\Model;

class RoomInventoryModel extends Model
{
    protected $table = 'room_inventory';
    protected $primaryKey = 'id';
    protected $allowedFields = ['room_type_id', 'room_number', 'floor', 'status'];

    public function withRoomType()
    {
        return $this->select('room_inventory.*, room_types.name AS room_type_name')
            ->join('room_types', 'room_types.id = room_inventory.room_type_id');
    }
}
