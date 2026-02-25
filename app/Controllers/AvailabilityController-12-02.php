<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Database\Exceptions\DatabaseException;

class AvailabilityController extends ResourceController
{
    public function check()
    {
        $rtId = $this->request->getGet('room_type_id');
        $in = $this->request->getGet('check_in');
        $out = $this->request->getGet('check_out');
        $excludeBookingId = $this->request->getGet('exclude_booking_id');

        if (!$rtId||!$in||!$out)
            return $this->failValidationError('Missing required params.');

        $db = \Config\Database::connect();
        $rt = $db->table('room_types')->getWhere(['id'=>$rtId])->getRow();
        if (!$rt) return $this->failNotFound('Room type not found.');

        $total = (int)$db->table('room_inventory')->where('room_type_id',$rtId)->countAllResults();

        // date overrides not included in this example

        // count overlapping bookings
        $cnt = $db->table('bookings')
            ->where('room_type_id',$rtId)
            ->where('status !=','cancelled')
            ->groupStart()
              ->where('check_in <',$out)
              ->where('check_out >',$in)
            ->groupEnd()
            ->countAllResults();

        $avail = $total - $cnt;
        
        
        $sub = $db->table('bookings')
            ->select('room_inventory_id')
            ->where('check_in <', $in)
            ->where('check_out >', $out)
            ->getCompiledSelect();

        // Main query with raw subquery
        $builder = $db->table('room_inventory')
            ->select('id, room_number,floor')
            ->where('room_type_id', $rtId)
            ->whereNotIn('room_inventory.id', $sub, false); // `false` disables escaping
        
        

        $inventories = $builder->get()->getResult();
        

        return $this->respond(['available'=>$avail > 0, 'available_rooms'=>$avail, 'total_rooms'=>$total, 'available_inventory'=>$inventories]);
    }
    
    
    
    
    public function calendar()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('bookings')
            ->select('room_type_id, check_in, check_out, COUNT(*) AS count')
            ->groupBy('room_type_id, check_in, check_out')
            ->get()
            ->getResult();

        $events = [];
        foreach ($builder as $row) {
            $events[] = [
                'title' => $row->count . " booked",
                'start' => $row->check_in,
                'end'   => date('Y-m-d', strtotime($row->check_out . ' +1 day')),
                'color' => '#f87171' // red shade
            ];
        }
        
        
        
        /*$roomTypes = $db->table('room_types')->get()->getResult();

        $startDate = new \DateTime('2025-08-01');
        $endDate = new \DateTime('2025-08-07');

        

        foreach ($roomTypes as $room) {
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($startDate, $interval, $endDate);

            foreach ($period as $date) {
                // Fetch booked count
                $count = $db->table('bookings')
                    ->where('room_type_id', $room->id)
                    ->where('check_in <=', $date->format('Y-m-d'))
                    ->where('check_out >', $date->format('Y-m-d'))
                    ->countAllResults();

                $available = max(0, $room->total_rooms - $count);

                $events[] = [
                    'title' => "{$room->name}: {$available} available",
                    'start' => $date->format('Y-m-d'),
                    'color' => $available > 0 ? '#34d399' : '#f87171'
                ];
            }
        }*/

        return $this->response->setJSON($events);

        //return $this->respond($out);
    }
    
    
    public function summary()
    {
        $startDate = $this->request->getGet('start');
        $endDate = $this->request->getGet('end');

        if (!$startDate || !$endDate) {
            return $this->failValidationError('Start and end dates are required.');
        }

        $db = \Config\Database::connect();

        // Step 1: Get all room types
        $roomTypes = $db->table('room_types')->get()->getResultArray();

        // Step 2: Get booked counts per room type within range
        $booked = $db->table('bookings')
            ->select('room_type_id, COUNT(*) AS booked_count')
            ->where('check_in <', $endDate)
            ->where('check_out >', $startDate)
            ->groupBy('room_type_id')
            ->get()->getResultArray();

        $bookedMap = array_column($booked, 'booked_count', 'room_type_id');

        // Step 3: Calculate availability per room type
        $availability = [];
        foreach ($roomTypes as $room) {
            $totalRooms = $room['total_rooms'] ?? 0;
            $bookedCount = $bookedMap[$room['id']] ?? 0;
            $availability[] = [
                'room_type_id' => $room['id'],
                'room_type' => $room['name'],
                'total_rooms' => $totalRooms,
                'booked' => $bookedCount,
                'available' => max(0, $totalRooms - $bookedCount),
            ];
        }

        // Step 4: Get detailed bookings in range
        $bookings = $db->table('bookings')
            ->select('bookings.*, customers.name as customer_name, room_types.name as room_type')
            ->join('customers', 'customers.id = bookings.customer_id', 'left')
            ->join('room_types', 'room_types.id = bookings.room_type_id', 'left')
            ->where('check_in <', $endDate)
            ->where('check_out >', $startDate)
            ->orderBy('check_in', 'ASC')
            ->get()->getResult();

        return $this->respond([
            'availability' => $availability,
            'bookings' => $bookings
        ]);
    }
    
    
    public function availableInventories()
    {
        $db = \Config\Database::connect();
        $roomTypeId = $this->request->getGet('room_type_id');
        $checkIn = $this->request->getGet('check_in');
        $checkOut = $this->request->getGet('check_out');
        
        // return $this->respond($this->request->getGet());

        if (!$roomTypeId || !$checkIn || !$checkOut) {
           return $this->failValidationErrors('Missing parameters.');
        }

        /*$builder = $db->table('room_inventory');
        $builder->select('room_inventory.id, room_inventory.room_number');
        $builder->where('room_type_id', $roomTypeId);

        // Exclude room inventories already booked
        $builder->whereNotIn('room_inventory.id', function($sub) use ($checkIn, $checkOut) {
            $sub->select('room_inventory_id')
                ->from('bookings')
                ->where('check_in <', $checkOut)
                ->where('check_out >', $checkIn);
        });

        $inventories = $builder->get()->getResult();*/
        
        // Build the subquery first
        $sub = $db->table('bookings')
            ->select('room_inventory_id')
            ->where('check_in <', $checkOut)
            ->where('check_out >', $checkIn)
            ->getCompiledSelect();

        // Main query with raw subquery
        $builder = $db->table('room_inventory')
            ->select('id, room_number,floor')
            ->where('room_type_id', $roomTypeId)
            ->whereNotIn('room_inventory.id', $sub, false); // `false` disables escaping

        $inventories = $builder->get()->getResult();
        

        return $this->respond($inventories);
    }



}
