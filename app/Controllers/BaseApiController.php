<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EmployeeModel;

class BaseApiController extends ResourceController
{
    protected function currentEmployee()
    {
        $auth = $this->request->getHeaderLine('Authorization');
        if (!$auth) return null;

        $token = '';
        if (preg_match('/Bearer\s(\S+)/', $auth, $m)) $token = $m[1];
        if (!$token) return null;

        $model = new EmployeeModel();
        return $model->where('api_token', $token)->first();
    }

    protected function currentEmployeeId()
    {
        $emp = $this->currentEmployee();
        return $emp ? (int)$emp['id'] : null;
    }
    
    protected function currentUserRole()
    {
        $emp = $this->currentEmployee();
        return $emp ? (int)$emp['role'] : null;
    }
}