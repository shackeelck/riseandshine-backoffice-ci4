<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomTypeModel extends Model
{
    protected $table = 'room_types';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name','description','total_rooms','max_guests','max_adults','max_child','status'];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

