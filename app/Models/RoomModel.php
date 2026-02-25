<?php namespace App\Models;
use CodeIgniter\Model;

class RoomModel extends Model {
  protected $table = 'rooms';
  protected $primaryKey = 'id';
  protected $allowedFields = ['number','type','price','status'];
}
