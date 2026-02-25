<?php
namespace App\Models;
use CodeIgniter\Model;

class TariffSupplementModel extends Model
{
    protected $table      = 'tariff_supplements';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tariff_id','name','price'];
}
