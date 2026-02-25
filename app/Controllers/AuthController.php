<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    public function login()
    {
        $data = $this->request->getJSON();
        $model = new EmployeeModel();

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $employee = $model->where('username', $username)->first();

        if (!$employee || !password_verify($password, $employee['password'])) {
            return $this->respond(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        // ✅ Always generate + store token (not only remember)
        $token = bin2hex(random_bytes(32));

        $model->update($employee['id'], [
            'api_token' => $token,
            'api_token_created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status' => 'success',
            'token'  => $token,
            'employee' => [
                'id' => (int) $employee['id'],
                'name' => $employee['name'] ?? $employee['username'],
                'username' => $employee['username'],
            ],
        ]);
    }
    
    public function logout()
    {
         // If you want logout to invalidate the token:
        $auth = $this->request->getHeaderLine('Authorization');
        $token = '';
        if (preg_match('/Bearer\s(\S+)/', $auth, $m)) $token = $m[1];

        if ($token) {
            $model = new EmployeeModel();
            $model->where('api_token', $token)->set(['api_token' => null, 'api_token_created_at' => null])->update();
        }

        return $this->respond([
            'status' => 200,
            'message' => 'Logged out successfully'
        ]);
    }
}
