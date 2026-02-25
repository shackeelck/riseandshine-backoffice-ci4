<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomAmenityModel extends Model
{
    protected $table = 'room_amenities';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'status'];
    protected $useTimestamps = false;
    protected $returnType = 'array';
}
