<?php

namespace App\Controllers;
use App\Models\RoomInventoryModel;
use CodeIgniter\RESTful\ResourceController;

class RoomInventoryController extends ResourceController
{
    protected $modelName = RoomInventoryModel::class;
    protected $format    = 'json';

    public function index()
    {
        return $this->respond($this->model->withRoomType()->findAll());
    }

    public function show($id = null)
    {
        $room = $this->model->find($id);
        return $room ? $this->respond($room) : $this->failNotFound('Room not found');
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (!isset($data['room_type_id'], $data['room_number'])) {
            return $this->failValidationError('Missing required fields');
        }

        $this->model->insert($data);
        return $this->respondCreated(['status' => 'success']);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $this->model->update($id, $data);
        return $this->respond(['status' => 'success']);
    }

    public function delete($id = null)
    {
        $this->model->delete($id);
        return $this->respondDeleted(['status' => 'deleted']);
    }
    
    public function getInventoryBy($BookingId){
         $db = \Config\Database::connect();
        
       
        
        // Main query with raw subquery
        $builder = $db->table('room_inventory')
            ->select('room_inventory.id, room_number,floor')
            ->where('bookings.id', $BookingId)
            ->join('bookings', 'bookings.room_type_id  = room_inventory.room_type_id', 'left')
            ; //->whereNotIn('room_inventory.id', $sub, false) `false` disables escaping
        
        

        $inventories = $builder->get()->getResult();
        return $this->respond($inventories);
    }
}
