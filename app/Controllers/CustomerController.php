<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CustomerModel;

class CustomerController extends ResourceController
{
    protected $modelName = CustomerModel::class;
    protected $format = 'json';

    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        return $data ? $this->respond($data) : $this->failNotFound('Customer not found');
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (empty($data['name'])) {
            return $this->failValidationError('Name is required');
        }

        $id = $this->model->insert($data);
        return $this->respondCreated(['status' => 'success', 'id' => $id]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (empty($data['name'])) {
            return $this->failValidationError('Name is required');
        }

        $this->model->update($id, $data);
        return $this->respond(['status' => 'success']);
    }

    public function delete($id = null)
    {
        $this->model->delete($id);
        return $this->respondDeleted(['status' => 'deleted']);
    }
}
