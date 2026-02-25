<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\RoomAmenityModel;

class RoomAmenityController extends ResourceController
{
    protected $modelName = RoomAmenityModel::class;
    protected $format    = 'json';

    public function index()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $builder = $this->model;

        if ($q !== '') {
            $builder = $builder->like('name', $q);
        }
        if ($status !== null && $status !== '') {
            $builder = $builder->where('status', (int)$status);
        }

        $rows = $builder->orderBy('id', 'DESC')->findAll();
        return $this->respond($rows);
    }

    public function show($id = null)
    {
        $row = $this->model->find((int)$id);
        return $row ? $this->respond($row) : $this->failNotFound('Amenity not found');
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];

        if (!isset($data['name']) || trim($data['name']) === '') {
            return $this->failValidationError('Name is required');
        }

        $insert = [
            'name' => trim($data['name']),
            'status' => isset($data['status']) ? (int)$data['status'] : 1,
        ];

        $this->model->insert($insert);
        return $this->respondCreated(['status' => 'success']);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? [];
        $id = (int)$id;

        if (!$this->model->find($id)) {
            return $this->failNotFound('Amenity not found');
        }

        $update = [];
        if (isset($data['name'])) $update['name'] = trim((string)$data['name']);
        if (isset($data['status'])) $update['status'] = (int)$data['status'];

        if (!$update) return $this->failValidationError('Nothing to update');

        $this->model->update($id, $update);
        return $this->respond(['status' => 'success']);
    }

    public function delete($id = null)
    {
        $id = (int)$id;
        if (!$this->model->find($id)) {
            return $this->failNotFound('Amenity not found');
        }
        $this->model->delete($id);
        return $this->respondDeleted(['status' => 'deleted']);
    }
}
