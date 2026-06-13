<?php

namespace App\Controllers;

class ReportsController extends BaseApiController
{
    protected $format = 'json';

    private $reports = [

        'arrival_forecast' => 'Arrival Forecast',
        'departure_forecast' => 'Departure Forecast',
        'inhouse_report' => 'Inhouse Report',
        'room_occupancy_report' => 'Room Occupancy Report',
        'breakfast_report' => 'Breakfast Report',
        'operator_performance' => 'Operator Performance',
        'market_wise_report' => 'Market Wise Report',
        'revenue_report' => 'Revenue Report',
    ];

    public function index()
    {
        $reports = [];

        foreach ($this->reports as $key => $name) {
            $reports[] = [
                'key' => $key,
                'name' => $name,
                'endpoint' => '/api/reports/' . str_replace('_', '-', $key),
            ];
        }

        return $this->respond([
            'data' => $reports,
        ]);
    }

    public function arrivalForecast()
    {

        return $this->dailyArrivalReport();
    }

    public function dailyArrivalReport()
    {
        $filters = $this->getArrivalFilters();

        if (!$filters['is_valid']) {
            return $this->failValidationError($filters['error']);
        }

        $db = db_connect();

        $primaryGuestSub = "(SELECT bg.name
            FROM booking_guests bg
            WHERE bg.booking_id = b.id AND bg.is_primary = 1
            LIMIT 1)";

        $nationalitySub = "(SELECT c.name
            FROM booking_guests bg
            LEFT JOIN countries c ON c.id = bg.nationality_id
            WHERE bg.booking_id = b.id AND bg.is_primary = 1
            LIMIT 1)";

        $builder = $db->table('bookings b')
            ->select("
                b.reference AS booking_number,
                {$primaryGuestSub} AS guest_name,
                {$nationalitySub} AS nationality,
                customers.name AS operator_agent,
                room_inventory.room_number,
                room_types.name AS room_type,
                b.guests AS number_of_guests,
                b.check_in AS check_in_date,
                DATEDIFF(b.check_out, b.check_in) AS number_of_nights,
                b.arrival_flight,
                b.special_request AS remarks
            ", false)
            ->join('customers', 'customers.id = b.customer_id', 'left')
            ->join('room_inventory', 'room_inventory.id = b.room_inventory_id', 'left')
            ->join('room_types', 'room_types.id = b.room_type_id', 'left')
            ->where('b.check_in >=', $filters['from'])
            ->where('b.check_in <=', $filters['to'])
            ->where('b.status !=', 'cancelled')
            ->orderBy('b.check_in', 'ASC')
            ->orderBy('room_inventory.room_number', 'ASC')
            ->orderBy('b.reference', 'ASC');

        $rows = $builder->get()->getResultArray();

        return $this->respond([
            'report_key' => 'arrival_forecast',
            'report_name' => $this->reports['arrival_forecast'],
            'filters' => [
                'arrival_filter' => $filters['arrival_filter'],
                'from' => $filters['from'],
                'to' => $filters['to'],
            ],
            'columns' => [
                'booking_number',
                'guest_name',
                'nationality',
                'operator_agent',
                'room_number',
                'room_type',
                'number_of_guests',
                'check_in_date',
                'number_of_nights',
                'arrival_flight',
                'remarks',
            ],
            'summary' => [
                'total_arrivals' => count($rows),
                'total_guests' => $this->sumColumn($rows, 'number_of_guests'),
            ],
            'data' => $rows,
        ]);

    }

    public function departureForecast()
    {
        return $this->dailyDepartureReport();
    }

    public function dailyDepartureReport()
    {
        $filters = $this->getDepartureFilters();

        if (!$filters['is_valid']) {
            return $this->failValidationError($filters['error']);
        }

        $db = db_connect();

        $primaryGuestSub = "(SELECT bg.name
            FROM booking_guests bg
            WHERE bg.booking_id = b.id AND bg.is_primary = 1
            LIMIT 1)";

        $outstandingBalanceSub = "(SELECT COALESCE(p.total, 0) - COALESCE(p.paid_amount, 0)
            FROM proforma_bookings pb
            LEFT JOIN proformas p ON p.id = pb.proforma_id
            WHERE pb.booking_id = b.id
            ORDER BY pb.id DESC
            LIMIT 1)";

        $builder = $db->table('bookings b')
            ->select("
                b.reference AS booking_number,
                {$primaryGuestSub} AS guest_name,
                room_inventory.room_number,
                customers.name AS operator,
                b.guests AS number_of_guests,
                b.check_out AS check_out_date,
                COALESCE({$outstandingBalanceSub}, 0) AS outstanding_balance,
                b.arrival_flight,
                b.special_request AS remarks
            ", false)
            ->join('customers', 'customers.id = b.customer_id', 'left')
            ->join('room_inventory', 'room_inventory.id = b.room_inventory_id', 'left')
            ->where('b.check_out >=', $filters['from'])
            ->where('b.check_out <=', $filters['to'])
            ->where('b.status !=', 'cancelled')
            ->orderBy('b.check_out', 'ASC')
            ->orderBy('room_inventory.room_number', 'ASC')
            ->orderBy('b.reference', 'ASC');

        $rows = $builder->get()->getResultArray();

        return $this->respond([
            'report_key' => 'departure_forecast',
            'report_name' => $this->reports['departure_forecast'],
            'filters' => [
                'departure_filter' => $filters['departure_filter'],
                'from' => $filters['from'],
                'to' => $filters['to'],
            ],
            'columns' => [
                'booking_number',
                'guest_name',
                'room_number',
                'operator',
                'number_of_guests',
                'check_out_date',
                'outstanding_balance',
                'arrival_flight',
                'remarks',
            ],
            'summary' => [
                'total_departures' => count($rows),
                'total_guests' => $this->sumColumn($rows, 'number_of_guests'),
                'total_outstanding_balance' => $this->sumDecimalColumn($rows, 'outstanding_balance'),
            ],
            'data' => $rows,
        ]);
    }

    public function inhouseReport()
    {
        return $this->reportResponse('inhouse_report');
    }

    public function roomOccupancyReport()
    {
        return $this->reportResponse('room_occupancy_report');
    }

    public function breakfastReport()
    {
        return $this->reportResponse('breakfast_report');
    }

    public function operatorPerformance()
    {
        return $this->reportResponse('operator_performance');
    }

    public function marketWiseReport()
    {
        return $this->reportResponse('market_wise_report');
    }

    public function revenueReport()
    {
        return $this->reportResponse('revenue_report');
    }

    private function reportResponse($key)
    {
        $filters = $this->getCommonFilters();

        if (!$filters['is_valid']) {
            return $this->failValidationError($filters['error']);
        }

        unset($filters['is_valid'], $filters['error']);

        return $this->respond([
            'report_key' => $key,
            'report_name' => $this->reports[$key],
            'filters' => $filters,
            'data' => [],
            'summary' => [],
            'message' => 'Report response details are pending and will be implemented in the next step.',
        ]);
    }

    private function getCommonFilters()
    {
        $from = trim((string) ($this->request->getGet('from') ?? ''));
        $to = trim((string) ($this->request->getGet('to') ?? ''));

        if ($from === '') {
            $from = date('Y-m-d');
        }

        if ($to === '') {
            $to = $from;
        }

        if (!$this->isValidDate($from)) {
            return [
                'is_valid' => false,
                'error' => 'from must be a valid date in YYYY-MM-DD format.',
            ];
        }

        if (!$this->isValidDate($to)) {
            return [
                'is_valid' => false,
                'error' => 'to must be a valid date in YYYY-MM-DD format.',
            ];
        }

        if (strtotime($to) < strtotime($from)) {
            return [
                'is_valid' => false,
                'error' => 'to must be greater than or equal to from.',
            ];
        }

        return [
            'is_valid' => true,
            'error' => null,
            'from' => $from,
            'to' => $to,
            'customer_id' => $this->request->getGet('customer_id'),
            'room_type_id' => $this->request->getGet('room_type_id'),
            'operator_id' => $this->request->getGet('operator_id'),
            'market' => $this->request->getGet('market'),
            'status' => $this->request->getGet('status'),
        ];
    }

   
    private function getArrivalFilters()
    {
        $arrivalFilter = trim((string) ($this->request->getGet('filter') ?? 'today'));

        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        switch ($arrivalFilter) {
            case 'today':


                $from = $today;
                $to = $today;
                break;

            case 'tomorrow':

                $from = $tomorrow;
                $to = $tomorrow;
                break;

           
            case 'custom':

                $from = trim((string) ($this->request->getGet('from') ?? ''));
                $to = trim((string) ($this->request->getGet('to') ?? ''));

                if ($from === '' || $to === '') {
                    return [
                        'is_valid' => false,
                        'error' => 'from and to are required for custom arrival date range.',
                    ];
                }
                break;

            default:
                return [
                    'is_valid' => false,
                    'error' => 'filter must be arrival_today, arrival_tomorrow, custom, or custom_date_range.',
                ];
        }

        if (!$this->isValidDate($from)) {
            return [
                'is_valid' => false,
                'error' => 'from must be a valid date in YYYY-MM-DD format.',
            ];
        }

        if (!$this->isValidDate($to)) {
            return [
                'is_valid' => false,
                'error' => 'to must be a valid date in YYYY-MM-DD format.',
            ];
        }

        if (strtotime($to) < strtotime($from)) {
            return [
                'is_valid' => false,
                'error' => 'to must be greater than or equal to from.',
            ];
        }

        return [
            'is_valid' => true,
            'error' => null,
            'arrival_filter' => $arrivalFilter,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function getDepartureFilters()
    {
        $departureFilter = trim((string) ($this->request->getGet('filter') ?? 'departure_today'));

        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        switch ($departureFilter) {
            case 'departure_today':
            case 'today':
                $from = $today;
                $to = $today;
                break;

            case 'departure_tomorrow':
            case 'tomorrow':
                $from = $tomorrow;
                $to = $tomorrow;
                break;

            case 'custom_date_range':
            case 'custom':
                $from = trim((string) ($this->request->getGet('from') ?? ''));
                $to = trim((string) ($this->request->getGet('to') ?? ''));

                if ($from === '' || $to === '') {
                    return [
                        'is_valid' => false,
                        'error' => 'from and to are required for custom departure date range.',
                    ];
                }
                break;

            default:
                return [
                    'is_valid' => false,
                    'error' => 'filter must be departure_today, departure_tomorrow, custom_date_range, today, tomorrow, or custom.',
                ];
        }

        if (!$this->isValidDate($from)) {
            return [
                'is_valid' => false,
                'error' => 'from must be a valid date in YYYY-MM-DD format.',
            ];
        }

        if (!$this->isValidDate($to)) {
            return [
                'is_valid' => false,
                'error' => 'to must be a valid date in YYYY-MM-DD format.',
            ];
        }

        if (strtotime($to) < strtotime($from)) {
            return [
                'is_valid' => false,
                'error' => 'to must be greater than or equal to from.',
            ];
        }

        return [
            'is_valid' => true,
            'error' => null,
            'departure_filter' => $departureFilter,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function sumColumn($rows, $column)
    {
        $total = 0;

        foreach ($rows as $row) {
            $total += (int) ($row[$column] ?? 0);
        }

        return $total;
    }

    private function sumDecimalColumn($rows, $column)
    {
        $total = 0.0;

        foreach ($rows as $row) {
            $total += (float) ($row[$column] ?? 0);
        }

        return round($total, 2);
    }


    private function isValidDate($date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parts = explode('-', $date);

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }
}
