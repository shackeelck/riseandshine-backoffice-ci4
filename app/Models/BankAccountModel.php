<?php

namespace App\Models;

use CodeIgniter\Model;

class BankAccountModel extends Model
{
    protected $table = 'bank_accounts';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'bank_name',
        'account_no',
        'bank_code',
        'bank_details',
        'currency',
        'is_default',
        'status',
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';
}
