<?php

namespace App\Models;

use CodeIgniter\Model;

class MinibarItemModel extends Model
{
    protected $table = 'minibar_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'price', 'status'];
    protected $useTimestamps = false;
    protected $returnType = 'array';
}
