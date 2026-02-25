<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\BookingModel;
use App\Models\RoomInventoryModel;
use CodeIgniter\Database\Exceptions\DatabaseException;


class BookingController extends ResourceController
{
    protected $modelName = BookingModel::class;
    protected $format    = 'json';

    public function index()
    {
        $db = \Config\Database::connect();
        $session = session();
        echo ($session->get('user_name'));
        // ---- Query params ----
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = (int) ($this->request->getGet('perPage') ?? 15);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 15;

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $filter = (string) ($this->request->getGet('filter') ?? '');

        $t = date('Y-m-d');
        $tom = date('Y-m-d', strtotime('+1 day'));

        $builder = $db->table('bookings b');
        
        // Guests subqueries
        $primaryGuestSub = "(SELECT bg.name 
            FROM booking_guests bg 
            WHERE bg.booking_id = b.id AND bg.is_primary = 1 
            LIMIT 1)";

        $primaryCountrySub = "(SELECT c.name 
            FROM booking_guests bg 
            LEFT JOIN countries c ON c.id = bg.nationality_id
            WHERE bg.booking_id = b.id AND bg.is_primary = 1
            LIMIT 1)";

        $extraGuestsSub = "(SELECT GROUP_CONCAT(bg.name SEPARATOR ', ')
            FROM booking_guests bg
            WHERE bg.booking_id = b.id AND bg.is_primary = 0)";
        
        
        $builder->select("
            b.*,
            customers.name AS customer_name,
            room_types.name AS room_type,
            room_inventory.room_number,
            bkd.username as bookedby,
            ent.username as enteredby,
            {$primaryGuestSub} AS primary_guest_name,
            {$primaryCountrySub} AS primary_guest_country,
            {$extraGuestsSub} AS extra_guest_names
        ");
        $builder->join('customers', 'customers.id = b.customer_id', 'left');
        $builder->join('room_types', 'room_types.id = b.room_type_id', 'left');
        $builder->join('room_inventory', 'room_inventory.id = b.room_inventory_id', 'left');
        // ✅ Join primary guest
        $builder->join('booking_guests bg','bg.booking_id = b.id AND bg.is_primary = 1','left'        );
        $builder->join('countries c', 'c.id = bg.nationality_id', 'left');
        
        $builder->join('employees bkd', 'bkd.id = b.booked_by', 'left');
        $builder->join('employees ent', 'ent.id = b.created_by', 'left');
        
        
            // ---- Search ----
            if ($q !== '') {
                $like = $db->escapeLikeString($q);

                $builder->groupStart()
                    ->like('customers.name', $q)
                    ->orLike('room_types.name', $q)
                    ->orLike('b.reference', $q)
                    ->orLike('b.customer_ref_no', $q)
                    // ✅ subquery search (NO RawSql, NO CI version issues)
                    ->orWhere("{$primaryGuestSub} LIKE '%{$like}%'", null, false)
                ->groupEnd();
            }
        
        
        
          // ---- Filters ----
        switch ($filter) {
            case 'arrival_today':
                $builder->where('b.check_in', $t);
                break;
            case 'arrival_tomorrow':
                $builder->where('b.check_in', $tom);
                break;
            case 'departure_today':
                $builder->where('b.check_out', $t);
                break;
            case 'departure_tomorrow':
                $builder->where('b.check_out', $tom);
                break;
            case 'inhouse':
                $builder->where('b.status', 'checked_in');
                $builder->where('b.check_in <=', $t);
                $builder->where('b.check_out >', $t);
                break;
        }
        
        
        // ---- Date filter (type + date) ----
         $dateFilterType = (string) ($this->request->getGet('date_filter_type') ?? '');
        $dateFilterDate = (string) ($this->request->getGet('date_filter_date') ?? '');
        
        if ($dateFilterType !== '' && $dateFilterDate !== '') {
            
            switch ($dateFilterType) {
                case 'arrival': // check_in
                    $builder->where('b.check_in', $dateFilterDate);
                    break;

                case 'departure': // check_out
                    $builder->where('b.check_out', $dateFilterDate);
                    break;

                case 'booking': // DATE(created_at)
                    $builder->where("DATE(b.created_at) = " . $db->escape($dateFilterDate), null, false);
                    break;

                case 'actual_checkin': // assumes b.actual_check_in exists (DATETIME)
                    $builder->where("DATE(b.actual_check_in) = " . $db->escape($dateFilterDate), null, false);
                    break;

                case 'actual_checkout': // assumes b.actual_check_out exists (DATETIME)
                    $builder->where("DATE(b.actual_check_out) = " . $db->escape($dateFilterDate), null, false);
                    break;
            }
        }

        $countBuilder = clone $builder;
        $countBuilder->select('COUNT(DISTINCT b.id) AS total', false);
        $countBuilder->orderBy('', '', false);

        $totalRow = $countBuilder->get()->getRowArray();
        $total = (int) ($totalRow['total'] ?? 0);

        // ---- Pagination ----
        $offset = ($page - 1) * $perPage;
        $builder->orderBy('b.id', 'DESC');
        $builder->limit($perPage, $offset);

        $rows = $builder->get()->getResultArray();
        
        //echo $db->getLastQuery();

         return $this->respond([
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => (int) ceil($total / $perPage),
            ]
        ]);
    }

    public function show($id = null)
    {
        $booking = $this->model->find($id);
        return $booking ? $this->respond($booking) : $this->failNotFound('Booking not found');
    }

   public function create()
   {
        $db = \Config\Database::connect();
        

        $data = $this->request->getJSON(true);
       
        // Validate basic required fields
        if (!isset($data['customer_id'], $data['room_type_id'], $data['check_in'], $data['check_out'])) {
            return $this->failValidationErrors('Required fields missing.');
        }
       
        // Optional: Check room inventory is available
        if (!empty($data['room_inventory_id'])) {
            $roominvmodel = new roomInventoryModel();
            $inventory = $roominvmodel
                ->where('room_type_id', $data['room_type_id'])
                ->where('id', $data['room_inventory_id'])
                ->first();

            if (!$inventory) {
                return $this->failValidationErrors('Invalid room inventory selection.');
            }
        }
       
        $data['room_inventory_id'] = $data['room_inventory_id'] ?? null;
       
        $data['reference'] = $this->generateReference();
       
        $data['arrival_flight']    = $data['arrival_flight'] ?? null;
        $data['departure_flight']  = $data['departure_flight'] ?? null;
       
        ['primaryGuest' => $p, 'extraGuests' => $eg] = $data;

        // Insert booking
        $booking_id = $this->model->insert($data);
        if (!$booking_id) return $this->failServerError();
    

        $builder = $db->table('booking_guests'); 
        // Add primary guest
        $builder->insert([
            'booking_id' => $booking_id,
            'is_primary' => 1,
            'name'       => $p['name'],
            'contact'    => $p['contact'],
            'email'      => $p['email'] ?? null,
            'dob'        => $p['dob'] ?? null,
            'id_proof'   => $p['idProof'] ?? null,
            'nationality_id'   => $p['nationality_id'] ?? null,
        ]);

        // Add extra guests
        foreach ($eg as $guest) {
            if (empty($guest['name']) ) continue;
            $builder->insert([
                'booking_id' => $booking_id,
                'is_primary' => 0,
                'name'       => $guest['name'],
                'dob'        => $guest['dob'] ?? null,
                'id_proof'   => $guest['idProof'] ?? null,
                'nationality_id'   => $guest['nationality_id'] ?? null,
            ]);
        }

        // Send email notification (do NOT fail booking if email fails)
        $emailStatus = 'sent';
        $emailError  = null;

        try {
            $sent = $this->sendBookingCreatedEmail($booking_id);
            if (!$sent) {
                $emailStatus = 'failed';
                $emailError  = 'Email service returned false (check SMTP/config).';
            }
        } catch (\Throwable $e) {
            $emailStatus = 'failed';
            $emailError  = $e->getMessage();
        }

        return $this->respondCreated(['status' => 'success', 'booking_id' => $booking_id]);
   }

   
/**
 * Email notification after booking creation
 */
protected function sendBookingCreatedEmail(int $bookingId): bool
{
    $db = \Config\Database::connect();

    // Load booking with useful joins for email
    $booking = $db->table('bookings')
        ->select('bookings.*, customers.name AS customer_name, customers.email AS customer_email, room_types.name AS room_type, room_inventory.room_number')
        ->join('customers', 'customers.id = bookings.customer_id', 'left')
        ->join('room_types', 'room_types.id = bookings.room_type_id', 'left')
        ->join('room_inventory', 'room_inventory.id = bookings.room_inventory_id', 'left')
        ->where('bookings.id', $bookingId)
        ->get()
        ->getRowArray();

    if (!$booking) {
        throw new \RuntimeException('Booking not found for email.');
    }

    // Load primary guest
    $primary = $db->table('booking_guests')
        ->where('booking_id', $bookingId)
        ->where('is_primary', 1)
        ->get()
        ->getRowArray();

    $to = "mirsaad@gmail.com";

    // Fallback: if not set, use Email config fromAddress as recipient (or set your own)
    if (!$to) {
        $emailConfig = config('Email');
        $to = $emailConfig->fromEmail ?? 'hello@riseandshinehotel.com';
    }
    if (!$to) {
        throw new \RuntimeException('BOOKING_NOTIFY_EMAIL not set and no fallback recipient found.');
    }

    $email = \Config\Services::email();

    $subject = 'New Booking Created: ' . ($booking['reference'] ?? ('#' . $bookingId));

    $roomNo = $booking['room_number'] ? $booking['room_number'] : 'Not assigned';

    $text = "New Booking Created\n"
        . "Reference: {$booking['reference']}\n"
        . "Customer/Agent: {$booking['customer_name']}\n"
        . "Room Type: {$booking['room_type']}\n"
        . "Room: {$roomNo}\n"
        . "Check-In: {$booking['check_in']}\n"
        . "Check-Out: {$booking['check_out']}\n"
        . "Guests: {$booking['guests']}\n"
        . "Status: {$booking['status']}\n"
        . "Primary Guest: " . ($primary['name'] ?? '-') . "\n"
        . "Contact: " . ($primary['contact'] ?? '-') . "\n"
        . "Arrival Flight: " . ($booking['arrival_flight'] ?? '-') . "\n"
        . "Departure Flight: " . ($booking['departure_flight'] ?? '-') . "\n";

    $html = '
      <div style="font-family:Arial,sans-serif;max-width:700px">
        <h2 style="margin:0 0 12px">New Booking Created</h2>
        <table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;font-size:14px">
          <tr><th align="left">Reference</th><td>' . esc($booking['reference']) . '</td></tr>
          <tr><th align="left">Customer/Agent</th><td>' . esc($booking['customer_name'] ?? '-') . '</td></tr>
          <tr><th align="left">Room Type</th><td>' . esc($booking['room_type'] ?? '-') . '</td></tr>
          <tr><th align="left">Room</th><td>' . esc($roomNo) . '</td></tr>
          <tr><th align="left">Check-In</th><td>' . esc($booking['check_in']) . '</td></tr>
          <tr><th align="left">Check-Out</th><td>' . esc($booking['check_out']) . '</td></tr>
          <tr><th align="left">Guests</th><td>' . esc($booking['guests']) . '</td></tr>
          <tr><th align="left">Status</th><td>' . esc($booking['status']) . '</td></tr>
          <tr><th align="left">Primary Guest</th><td>' . esc($primary['name'] ?? '-') . '</td></tr>
          <tr><th align="left">Contact</th><td>' . esc($primary['contact'] ?? '-') . '</td></tr>
          <tr><th align="left">Arrival Flight</th><td>' . esc($booking['arrival_flight'] ?? '-') . '</td></tr>
          <tr><th align="left">Departure Flight</th><td>' . esc($booking['departure_flight'] ?? '-') . '</td></tr>
        </table>
        <p style="margin-top:14px;color:#666;font-size:12px">Rise &amp; Shine  </p>
      </div>
    ';

    $email->setTo($to);
    $cc = ['it@tcm.travel','reception@riseandshinehotel.com'];
    $email->setCC(array_map('trim', explode(',', $cc)));
    $email->setSubject($subject);
    $email->setMessage($html);
    $email->setAltMessage($text);

    return $email->send();
}
    
    


    public function update($id = null)
    {
        $db = \Config\Database::connect();
        $guestTable = $db->table('booking_guests');
        $data = $this->request->getJSON(true);
        
        ['primaryGuest' => $p, 'extraGuests' => $eg] = $data;
        

        $this->model->update($id, $data);
        
        // Refresh guest data
        $guestTable->where('booking_id', $id)->delete();
        if($p['dob'] == '0000-00-00'){$p['dob'] = null;}
        $post = [
            'booking_id' => $id,
            'is_primary' => 1,
            'name'       => $p['name'],
            'contact'    => $p['contact'],
            'email'      => $p['email'] ?? null,
            'dob'        => $p['dob'] ?? null,
            'id_proof'   => $p['id_proof'] ?? null,
            'nationality_id'   => $p['nationality_id'] ?? null
        ];
            
        
        $guestTable->insert( $post);
        
        

        foreach ($eg as $guest) {
            if (empty($guest['name'])) continue;
            if($guest['dob'] == '0000-00-00'){$guest['dob'] = null;}
            $extrapost = [
                'booking_id' => $id,
                'is_primary' => 0,
                'name'       => $guest['name'],
                'dob'        => $guest['dob'] ?? null,
                'id_proof'   => $guest['id_proof'] ?? null,
                'nationality_id'   => $guest['nationality_id'] ?? null
            ];
            
          
            
            $guestTable->insert($extrapost);
        }

        return $this->respond(['status' => 'success']);
    }


    public function delete($id = null)
    {
        $this->model->delete($id);
        return $this->respondDeleted(['status' => 'deleted']);
    }
    
    public function guests($id = null)
    {
        $db = \Config\Database::connect();
        $guests = $db->table('booking_guests')
                     //->select("booking_guests.*, NULLIF(dob, '0000-00-00') AS dob")
                     ->where('booking_id', $id)
                     ->orderBy('is_primary', 'DESC')->orderBy('id', 'ASC')
                     ->get()
                     ->getResult();
        
        //print_r($guests);
        return $this->respond($guests);
    }
    
    
    protected function generateReference()
    {
        $year = date('y'); // "25" for 2025
        do {
            $num = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $ref = "$num-$year";
            $exists = $this->model->where('reference', $ref)->first();
        } while ($exists);
        return $ref;
    }
    
    /*private function saveGuests($bookingId, $primary, $extras)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('booking_guests');

        $builder->insert([... primary data ...]);

        foreach ($extras as $g) {
            if (empty($g['name']) || empty($g['contact'])) continue;
            $builder->insert([... extra data ...]);
        }
    }*/
}
