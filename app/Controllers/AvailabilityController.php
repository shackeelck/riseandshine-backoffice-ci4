<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class AvailabilityController extends ResourceController
{
    /**
     * GET api/availability/check
     * Params: room_type_id, check_in, check_out, (optional) exclude_booking_id
     */
    public function check()
    {
        $db = \Config\Database::connect();

        $rtId  = (int) $this->request->getGet('room_type_id');
        $in    = (string) $this->request->getGet('check_in');
        $out   = (string) $this->request->getGet('check_out');
        $excludeBookingId = $this->request->getGet('exclude_booking_id');

        if (!$rtId || !$in || !$out) {
            return $this->failValidationError('Missing required params: room_type_id, check_in, check_out');
        }

        // total rooms
        $total = (int) $db->table('room_inventory')
            ->where('room_type_id', $rtId)
            ->countAllResults();

        // ---- booked inventory subquery (overlap logic) ----
        $bookedInvSubBuilder = $db->table('bookings')
            ->select('room_inventory_id')
            ->where('room_type_id', $rtId)
            ->where('status !=', 'cancelled')
            ->where('room_inventory_id IS NOT NULL', null, false)
            ->groupStart()
                ->where('check_in <', $out)
                ->where('check_out >', $in)
            ->groupEnd();

        if (!empty($excludeBookingId)) {
            $bookedInvSubBuilder->where('id !=', (int) $excludeBookingId);
        }

        $bookedInvSub = $bookedInvSubBuilder->getCompiledSelect();

        // available inventories (actual list)
        $availableInventories = $db->table('room_inventory')
            ->select('id, room_number, floor, room_type_id')
            ->where('room_type_id', $rtId)
            ->whereNotIn('room_inventory.id', $bookedInvSub, false)
            ->orderBy('floor', 'ASC')
            ->orderBy('room_number', 'ASC')
            ->get()
            ->getResultArray();

        // booked count (distinct rooms)
        $countBuilder = $db->table('bookings')
            ->select('COUNT(DISTINCT room_inventory_id) AS cnt', false)
            ->where('room_type_id', $rtId)
            ->where('status !=', 'cancelled')
            ->where('room_inventory_id IS NOT NULL', null, false)
            ->groupStart()
                ->where('check_in <', $out)
                ->where('check_out >', $in)
            ->groupEnd();

        if (!empty($excludeBookingId)) {
            $countBuilder->where('id !=', (int) $excludeBookingId);
        }

        $row = $countBuilder->get()->getRowArray();
        $bookedCount = (int) ($row['cnt'] ?? 0);

        $availableRooms = max(0, $total - $bookedCount);

        return $this->respond([
            'available'           => $availableRooms > 0,
            'available_rooms'     => $availableRooms,
            'total_rooms'         => $total,
            'available_inventory' => $availableInventories,
        ]);
    }

    /**
     * GET api/availability/available-inventories
     * Params: room_type_id, check_in, check_out, (optional) exclude_booking_id
     */
    public function availableInventories()
    {
        $db = \Config\Database::connect();

        $roomTypeId = (int) $this->request->getGet('room_type_id');
        $checkIn    = (string) $this->request->getGet('check_in');
        $checkOut   = (string) $this->request->getGet('check_out');
        $excludeBookingId = $this->request->getGet('exclude_booking_id');

        if (!$roomTypeId || !$checkIn || !$checkOut) {
            return $this->failValidationError('Missing parameters: room_type_id, check_in, check_out');
        }

        $bookedInvSubBuilder = $db->table('bookings')
            ->select('room_inventory_id')
            ->where('room_type_id', $roomTypeId)
            ->where('status !=', 'cancelled')
            ->where('room_inventory_id IS NOT NULL', null, false)
            ->groupStart()
                ->where('check_in <', $checkOut)
                ->where('check_out >', $checkIn)
            ->groupEnd();

        if (!empty($excludeBookingId)) {
            $bookedInvSubBuilder->where('id !=', (int) $excludeBookingId);
        }

        $bookedInvSub = $bookedInvSubBuilder->getCompiledSelect();

        $inventories = $db->table('room_inventory')
            ->select('id, room_number, floor, room_type_id')
            ->where('room_type_id', $roomTypeId)
            ->whereNotIn('room_inventory.id', $bookedInvSub, false)
            ->orderBy('floor', 'ASC')
            ->orderBy('room_number', 'ASC')
            ->get()
            ->getResultArray();

        return $this->respond($inventories);
    }

    
/**
 * BookMyShow-style room map
 * Returns ALL inventories + is_booked flag for the selected date range
 */
public function roomMap()
{
    $db = \Config\Database::connect();

    $roomTypeId = $this->request->getGet('room_type_id');
    $checkIn    = $this->request->getGet('check_in');
    $checkOut   = $this->request->getGet('check_out');

    if (!$roomTypeId || !$checkIn || !$checkOut) {
        return $this->failValidationError('Missing parameters.');
    }

    // 1) all inventories in that room type
    $allRooms = $db->table('room_inventory')
        ->select('id, room_number, floor, room_type_id')
        ->where('room_type_id', $roomTypeId)
        ->orderBy('floor', 'ASC')
        ->orderBy('room_number', 'ASC')
        ->get()->getResultArray();

    // 2) booked inventories (overlap)
    $bookedBuilder = $db->table('bookings')
        ->select('room_inventory_id')
        ->where('room_type_id', $roomTypeId)
        ->where('status !=', 'cancelled')
        ->where('room_inventory_id IS NOT NULL', null, false)
        ->groupStart()
            ->where('check_in <', $checkOut)
            ->where('check_out >', $checkIn)
        ->groupEnd();

    $bookedRows = $bookedBuilder->get()->getResultArray();
    $bookedIds = [];
    foreach ($bookedRows as $r) {
        if (!empty($r['room_inventory_id'])) $bookedIds[] = (int)$r['room_inventory_id'];
    }
    $bookedSet = array_flip($bookedIds);

    // 3) mark rooms
    foreach ($allRooms as &$r) {
        $rid = (int)$r['id'];
        $r['is_booked'] = isset($bookedSet[$rid]) ? 1 : 0;
    }

    return $this->respond([
        'rooms' => $allRooms,
        'booked_ids' => $bookedIds,
    ]);
}
}
