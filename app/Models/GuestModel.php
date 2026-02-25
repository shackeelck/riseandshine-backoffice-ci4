<?php namespace App\Models;
use CodeIgniter\Model;

class GuestModel extends Model {
  protected $table = 'guest_registration';
  protected $primaryKey = 'guest_id';
  protected $allowedFields = ['guest_given_name','guest_sur_name','guest_nationality','guest_contact_no','guest_email','document_type','document_no','signature'];
}
