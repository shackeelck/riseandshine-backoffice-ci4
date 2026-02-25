<?php
namespace App\Models;
use CodeIgniter\Model;

class TariffModel extends Model
{
    protected $table      = 'tariffs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['room_type_id'];

    // Optionally auto-manage timestamps
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Fetch with child records
    public function withChildren()
    {
        return $this->select('tariffs.*, room_types.name AS room_type')
            ->join('room_types', 'room_types.id = tariffs.room_type_id');
    }
}
