<?php
namespace App\Models;
use CodeIgniter\Model;

class TariffTransferModel extends Model
{
    protected $table      = 'tariff_transfers';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tariff_id','type','price'];
}
