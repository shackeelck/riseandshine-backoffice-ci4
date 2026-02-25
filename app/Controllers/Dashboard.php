<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        
        if (!session()->get('logged_in')) return redirect()->to('/auth');
        echo view('templates/header');
        echo view('dashboard');
        echo view('templates/footer');
    }
}
