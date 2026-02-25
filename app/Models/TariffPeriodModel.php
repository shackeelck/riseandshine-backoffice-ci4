<?php
namespace App\Models;
use CodeIgniter\Model;

class TariffPeriodModel extends Model
{
    protected $table      = 'tariff_periods';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tariff_id','start_date','end_date','single_rate','double_rate','extra_rate'];
}
