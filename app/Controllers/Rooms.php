<?php namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;

class Rooms extends ResourceController {
  protected $modelName = 'App\Models\RoomModel';
  protected $format = 'json';
}
