<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class AvailabilityChartController extends ResourceController
{
    protected $format = 'json';

    // GET /api/availabilitychart?from=2026-01-01&months=4&on_request_threshold=2
    public function index()
    {
        $from = $this->request->getGet('from');
        $months = (int)($this->request->getGet('months') ?? 4);
        $threshold = (int)($this->request->getGet('on_request_threshold') ?? 2);

        if (!$from || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            return $this->failValidationError('from is required in YYYY-MM-DD');
        }

        $months = max(1, min(12, $months));
        $threshold = max(0, $threshold);

        $db = \Config\Database::connect();

        // room types (must have total_rooms/status columns in your schema)
        $roomTypes = $db->table('room_types')
            ->select('id, name, total_rooms')
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        // month ranges
        $monthRanges = [];
        $startTs = strtotime(date('Y-m-01', strtotime($from)));

        for ($i = 0; $i < $months; $i++) {
            $mStart = date('Y-m-01', strtotime("+$i month", $startTs));
            $mEnd   = date('Y-m-t', strtotime($mStart));
            $monthRanges[] = [
                'month' => date('M-y', strtotime($mStart)),
                'start' => $mStart,
                'end'   => $mEnd,
                'days'  => (int)date('t', strtotime($mStart)),
            ];
        }

        $globalStart = $monthRanges[0]['start'];
        $globalEnd = $monthRanges[count($monthRanges) - 1]['end'];

        // ✅ extend 1 day backward to compute "new stop" / "reopen"
        $extendedStart = date('Y-m-d', strtotime($globalStart . ' -1 day'));

        // bookings overlapping extended range
        $bookings = $db->table('bookings')
            ->select('id, room_type_id, check_in, check_out, status')
            ->where('check_in <=', $globalEnd)
            ->where('check_out >=', $extendedStart)
            ->whereIn('status', ['confirmed', 'pending'])
            ->get()->getResultArray();

        // total rooms per type from inventory (exclude maintenance)
        $invCounts = $db->table('room_inventory')
            ->select('room_type_id, SUM(CASE WHEN status != "maintenance" THEN 1 ELSE 0 END) AS inv_total')
            ->groupBy('room_type_id')
            ->get()->getResultArray();

        $invMap = [];
        foreach ($invCounts as $r) {
            $invMap[(int)$r['room_type_id']] = (int)$r['inv_total'];
        }

        // helper: status based on availability
        $baseStatus = function(int $available) use ($threshold) {
            if ($available <= 0) return 'stop';
            if ($threshold > 0 && $available <= $threshold) return 'on_request';
            return 'open';
        };

        // Build chart for each month
        $chart = [];

        foreach ($monthRanges as $mi => $mr) {
            // list of dates for this month
            $dates = [];
            for ($d = 1; $d <= $mr['days']; $d++) {
                $dates[$d] = date('Y-m-d', strtotime($mr['start'] . " +".($d-1)." day"));
            }

            foreach ($roomTypes as $rt) {
                $rtId = (int)$rt['id'];
                $total = $invMap[$rtId] ?? (int)$rt['total_rooms'];

                for ($d = 1; $d <= $mr['days']; $d++) {
                    $dayDate = $dates[$d];

                    // compute booked for day (night rule: check_in <= day < check_out)
                    $booked = 0;
                    foreach ($bookings as $b) {
                        if ((int)$b['room_type_id'] !== $rtId) continue;
                        if ($dayDate >= $b['check_in'] && $dayDate < $b['check_out']) $booked++;
                    }

                    $available = max($total - $booked, 0);
                    $status = $baseStatus($available);

                    // ✅ yesterday transition logic
                    $yesterday = date('Y-m-d', strtotime($dayDate . ' -1 day'));

                    // compute yesterday's booked/available/status quickly (same logic)
                    $yBooked = 0;
                    foreach ($bookings as $b) {
                        if ((int)$b['room_type_id'] !== $rtId) continue;
                        if ($yesterday >= $b['check_in'] && $yesterday < $b['check_out']) $yBooked++;
                    }
                    $yAvailable = max($total - $yBooked, 0);
                    $yStatus = $baseStatus($yAvailable);

                    // ✅ New stop sales: today stop but yesterday not stop
                    if ($status === 'stop' && $yStatus !== 'stop') {
                        $status = 'new_stop';
                    }

                    // ✅ Re-open: today open/on_request but yesterday stop
                    if (($status === 'open' || $status === 'on_request') && $yStatus === 'stop') {
                        $status = 'reopen';
                    }

                    $chart[$mi][$rtId][$d] = [
                        'total' => $total,
                        'booked' => $booked,
                        'available' => $available,
                        'status' => $status,
                        'date' => $dayDate
                    ];
                }
            }
        }

       $rtOut = [];
        foreach ($roomTypes as $r) {
            $rtOut[] = [
                'id' => (int) $r['id'],
                'name' => $r['name'],
            ];
        }

        return $this->respond([
            'room_types' => $rtOut,
            'months' => $monthRanges,
            'chart' => $chart,
            'on_request_threshold' => $threshold
        ]);

    }

    // ✅ Click cell details
    // GET /api/availabilitychart/details?date=2026-01-05&room_type_id=3
    public function details()
    {
        $date = $this->request->getGet('date');
        $roomTypeId = (int)$this->request->getGet('room_type_id');

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->failValidationError('date is required (YYYY-MM-DD)');
        }
        if ($roomTypeId < 1) {
            return $this->failValidationError('room_type_id is required');
        }

        $db = \Config\Database::connect();

        // bookings that cover the date (check_in <= date < check_out)
        $builder = $db->table('bookings b');
        $builder->select("
            b.id, b.reference, b.check_in, b.check_out, b.status,
            b.arrival_flight, b.departure_flight,
            c.name AS customer_name,
            rt.name AS room_type,
            ri.room_number,
            pg.name AS primary_guest_name
        ");
        $builder->join('customers c', 'c.id = b.customer_id', 'left');
        $builder->join('room_types rt', 'rt.id = b.room_type_id', 'left');
        $builder->join('room_inventory ri', 'ri.id = b.room_inventory_id', 'left');
        $builder->join('booking_guests pg', 'pg.booking_id = b.id AND pg.is_primary = 1', 'left');

        $builder->where('b.room_type_id', $roomTypeId);
        $builder->where('b.check_in <=', $date);
        $builder->where('b.check_out >', $date);
        $builder->whereIn('b.status', ['confirmed', 'pending','checked_in','checked_out']);
        $builder->orderBy('b.check_in', 'ASC');

        $rows = $builder->get()->getResultArray();

        return $this->respond([
            'date' => $date,
            'room_type_id' => $roomTypeId,
            'bookings' => $rows
        ]);
    }
}
