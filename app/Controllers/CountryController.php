<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class CountryController extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));

        $db = \Config\Database::connect();

        $builder = $db->table('countries')
            ->select('id, name')
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->limit(250);

        if ($q !== '') {
            $builder->like('name', $q);
        }

        return $this->respond($builder->get()->getResultArray());
    }
}
