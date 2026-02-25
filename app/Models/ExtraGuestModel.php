<?php namespace App\Models;
use CodeIgniter\Model;

class ExtraGuestModel extends Model {
  protected $table = 'additional_guests';
  protected $primaryKey = 'add_guest_id';
  protected $allowedFields = ['main_guestid','guest_name','nationality','document_type','document_no','date_of_birth'];
}
