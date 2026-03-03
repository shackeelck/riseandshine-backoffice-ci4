<?php

namespace App\Controllers;

use App\Models\BankAccountModel;
use CodeIgniter\RESTful\ResourceController;

class BankAccountController extends ResourceController
{
    protected $modelName = BankAccountModel::class;
    protected $format = 'json';

    public function index()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $builder = $this->model;

        if ($q !== '') {
            $builder = $builder
                ->groupStart()
                ->like('bank_name', $q)
                ->orLike('account_no', $q)
                ->orLike('bank_code', $q)
                ->orLike('currency', $q)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder = $builder->where('status', (int) $status);
        }

        return $this->respond($builder->orderBy('id', 'DESC')->findAll());
    }

    public function show($id = null)
    {
        $row = $this->model->find((int) $id);
        return $row ? $this->respond($row) : $this->failNotFound('Bank account not found');
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];

        $bankName = trim((string) ($data['bank_name'] ?? ''));
        $accountNo = trim((string) ($data['account_no'] ?? ''));

        if ($bankName === '') {
            return $this->failValidationError('Bank name is required');
        }

        if ($accountNo === '') {
            return $this->failValidationError('Account no is required');
        }

        $insert = [
            'bank_name' => $bankName,
            'account_no' => $accountNo,
            'bank_code' => trim((string) ($data['bank_code'] ?? '')),
            'bank_details' => trim((string) ($data['bank_details'] ?? '')),
            'currency' => strtoupper(trim((string) ($data['currency'] ?? ''))),
            'is_default' => $this->normalizeDefault($data['is_default'] ?? ($data['default'] ?? 'no')),
            'status' => isset($data['status']) ? (int) $data['status'] : 1,
        ];

        if ($insert['is_default'] === 'yes') {
            $this->model->builder()->set('is_default', 'no')->update();
        }

        $id = $this->model->insert($insert);
        return $this->respondCreated(['status' => 'success', 'id' => $id]);
    }

    public function update($id = null)
    {
        $id = (int) $id;
        $data = $this->request->getJSON(true) ?? [];

        if (!$this->model->find($id)) {
            return $this->failNotFound('Bank account not found');
        }

        $update = [];

        if (array_key_exists('bank_name', $data)) {
            $update['bank_name'] = trim((string) $data['bank_name']);
        }
        if (array_key_exists('account_no', $data)) {
            $update['account_no'] = trim((string) $data['account_no']);
        }
        if (array_key_exists('bank_code', $data)) {
            $update['bank_code'] = trim((string) $data['bank_code']);
        }
        if (array_key_exists('bank_details', $data)) {
            $update['bank_details'] = trim((string) $data['bank_details']);
        }
        if (array_key_exists('currency', $data)) {
            $update['currency'] = strtoupper(trim((string) $data['currency']));
        }
        if (array_key_exists('is_default', $data) || array_key_exists('default', $data)) {
            $update['is_default'] = $this->normalizeDefault($data['is_default'] ?? $data['default']);
        }
        if (array_key_exists('status', $data)) {
            $update['status'] = (int) $data['status'];
        }

        if (!$update) {
            return $this->failValidationError('Nothing to update');
        }

        if (($update['is_default'] ?? null) === 'yes') {
            $this->model->builder()->where('id !=', $id)->set('is_default', 'no')->update();
        }

        $this->model->update($id, $update);
        return $this->respond(['status' => 'success']);
    }

    public function delete($id = null)
    {
        $id = (int) $id;

        if (!$this->model->find($id)) {
            return $this->failNotFound('Bank account not found');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['status' => 'deleted']);
    }

    private function normalizeDefault($value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['yes', '1', 'true'], true) ? 'yes' : 'no';
    }
}
