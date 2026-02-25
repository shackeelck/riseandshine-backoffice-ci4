<?php

namespace App\Controllers;

use App\Models\RoomTypeModel;
use CodeIgniter\Controller;
use CodeIgniter\RESTful\ResourceController;

class RoomTypeController  extends ResourceController
{
    protected $modelName = 'App\Models\RoomTypeModel';
    protected $format    = 'json';
    
    public function index()
    {
        
        return $this->respond($this->model->findAll());
    }

   

    public function show($id = null)
    {
        $data = $this->model->find($id);
        return $data ? $this->respond($data) : $this->failNotFound('Rooms not found');
    }
    
    
    public function create()
    {
        $data = $this->request->getJSON(true);
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $this->model->insert($data);
        return $this->respondCreated(['message' => 'Room Type created']);
    }
    
    
    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        $this->model->update($id, $data);
        return $this->respond(['message' => 'Room Type updated']);
    }
    
    public function delete($id = null)
    {
        $this->model->delete($id);
        return $this->respondDeleted(['message' => 'Room Type deleted']);
    }
}
