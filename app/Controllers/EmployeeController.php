<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use CodeIgniter\Controller;
use CodeIgniter\RESTful\ResourceController;

class EmployeeController  extends ResourceController
{
    protected $modelName = 'App\Models\EmployeeModel';
    protected $format    = 'json';
    
    public function index()
    {
        
        return $this->respond($this->model->findAll());
    }

   

    public function show($id = null)
    {
        $data = $this->model->find($id);
        return $data ? $this->respond($data) : $this->failNotFound('Employee not found');
    }
    
    
    public function create()
    {
        //exit;
        $data = $this->request->getJSON(true);
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $this->model->insert($data);
        //print_r($data);
        //exit;
        return $this->respondCreated(['message' => 'Employee created']);
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
        return $this->respond(['message' => 'Employee updated']);
    }
    
    public function delete($id = null)
    {
        $this->model->delete($id);
        return $this->respondDeleted(['message' => 'Employee deleted']);
    }
}
