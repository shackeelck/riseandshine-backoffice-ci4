<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class FrontDeskController extends ResourceController
{
    public function getCheckin($bookingId)
    {
        $db = \Config\Database::connect();

         // booking + primary guest name + room type + assigned room
        $booking = $db->table('bookings b')
            ->select("
                b.*,
                customers.name AS customer_name,
                room_types.name AS room_type,
                ri.room_number AS room_number
            ")
            ->join('customers', 'customers.id = b.customer_id', 'left')
            ->join('room_types', 'room_types.id = b.room_type_id', 'left')
            ->join('room_inventory ri', 'ri.id = b.room_inventory_id', 'left')
            ->where('b.id', (int)$bookingId)
            ->get()->getRowArray();

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        // guests
        $guests = $db->table('booking_guests')
            ->select('booking_guests.*,c.name as guest_country')
            ->where('booking_id', (int)$bookingId)
            ->orderBy('is_primary', 'DESC')
           ->join('countries c', 'c.id = booking_guests.nationality_id', 'left')
            ->get()->getResultArray();
        

        // rooms for that room type (to show in grid)
        // status logic:
        // occupied = exists in checkins where checked_out_at IS NULL
        // assigned = booking.room_inventory_id
        // Get occupied rooms (checked-in bookings)
        $occupiedRoomIds = array_column(
            $db->table('bookings')
                ->select('room_inventory_id')
                ->where('status', 'checked_in')
                ->where('room_inventory_id IS NOT NULL', null, false)
                ->get()->getResultArray(),
            'room_inventory_id'
        );

        // Get all rooms of that room type
        $rooms = $db->table('room_inventory')
            ->select('id, room_number, floor, room_type_id')
            ->where('room_type_id', (int)$booking['room_type_id'])
            ->orderBy('room_number', 'ASC')
            ->get()->getResultArray();

        $assignedRoomId = $booking['room_inventory_id']
            ? (int)$booking['room_inventory_id']
            : null;

        $occupiedSet = array_flip(array_map('intval', $occupiedRoomIds));

        // Add status to rooms
        foreach ($rooms as &$r) {
            $rid = (int)$r['id'];

            if ($assignedRoomId && $rid === $assignedRoomId) {
                $r['status'] = 'assigned';
            } elseif (isset($occupiedSet[$rid])) {
                $r['status'] = 'occupied';
            } else {
                $r['status'] = 'available';
            }
        }
        unset($r);

        // minibar master
        $minibar = $db->table('minibar_items')
            ->select('id, name, price')
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        // amenities master
        $amenities = $db->table('room_amenities')
            ->select('id, name')
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return $this->respond([
            'booking' => $booking,
            'guests' => $guests,
            'rooms' => $rooms,
            'minibar' => $minibar,
            'amenities' => $amenities
        ]);
    }

    public function confirmCheckin($id)
    {
        $data = $this->request->getJSON(true);
        $db = \Config\Database::connect();

        $db->table('bookings')
            ->where('id', $id)
            ->update([
                'room_inventory_id' => $data['room_inventory_id'],
                'status' => 'checked_in',
                'actual_check_in' => date('Y-m-d H:i:s'),
                'early_check_in' => $data['early_check_in'] ?? 0,
                'late_check_out' => $data['late_check_out'] ?? 0
            ]);
        
       // echo $db->getLastQuery();

        // save minibar
        if (!empty($data['minibar'])) {
            foreach ($data['minibar'] as $m) {
                $db->table('booking_minibar')->insert([
                    'booking_id' => $id,
                    'minibar_item_id' => $m['id'],
                    'quantity' => $m['quantity'],
                    'unit_price' => $m['price'],
                    'total' => $m['quantity'] * $m['price']
                ]);
            }
        }

        return $this->respond(['status' => 'success']);
    }
    
    
    public function uploadPassport()
    {
        $guestId = (int) $this->request->getPost('guest_id');
        $file = $this->request->getFile('file');

        if (!$guestId || !$file || !$file->isValid()) {
            return $this->failValidationError('guest_id and valid file required');
        }

        $ext = $file->getClientExtension();
        $allowed = ['jpg','jpeg','png','pdf','webp'];

        if (!in_array(strtolower($ext), $allowed)) {
            return $this->failValidationError('Only JPG, PNG, PDF allowed');
        }

        $newName = 'passport_' . $guestId . '_' . time() . '.' . $ext;

        // store under /public/uploads/passports/
        $file->move(FCPATH . 'uploads/passports', $newName);

        $path = 'uploads/passports/' . $newName;

        $db = \Config\Database::connect();
        $db->table('booking_guests')->where('id', $guestId)->update([
            'passport_file' => $path
        ]);

        return $this->respond([
            'status' => 'success',
            'path' => $path
        ]);
    }

    
}
