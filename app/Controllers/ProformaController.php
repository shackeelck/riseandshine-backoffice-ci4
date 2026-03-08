<?php

namespace App\Controllers;

use Mpdf\Mpdf;

class ProformaController extends BaseApiController
{
    protected $format = 'json';

    /* =========================
       LIST PROFORMAS
    ==========================*/
    public function index()
    {
        $db = db_connect();

        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(10, (int)($this->request->getGet('perPage') ?? 15)));
        $q = trim($this->request->getGet('q') ?? '');

        $builder = $db->table('proformas p')
            ->select('p.*, customers.name AS customer_name,cr.username as createdby,up.username as updatedby')
            ->join('customers', 'customers.id = p.customer_id', 'left')
            ->join('employees cr', 'cr.id = p.created_by', 'left')
            ->join('employees up', 'up.id = p.updated_by', 'left');

        if ($q !== '') {
            $builder->groupStart()
                ->like('p.proforma_no', $q)
                ->orLike('customers.name', $q)
            ->groupEnd();
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $rows = $builder
            ->orderBy('p.id', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        $proformaIds = array_values(array_filter(array_map(static function ($row) {
            return (int)($row['id'] ?? 0);
        }, $rows)));
        $bookingsByProforma = [];

        if ($proformaIds !== []) {
            $bookingRows = $db->table('proforma_bookings pb')
                ->select('pb.proforma_id, b.id, b.reference, b.check_in, b.check_out, b.status, bg.name AS primary_guest')
                ->join('bookings b', 'b.id = pb.booking_id', 'left')
                ->join('booking_guests bg', 'bg.booking_id = b.id AND bg.is_primary = 1', 'left')
                ->whereIn('pb.proforma_id', $proformaIds)
                ->orderBy('b.check_in', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($bookingRows as $bookingRow) {
                $proformaId = (int)$bookingRow['proforma_id'];
                unset($bookingRow['proforma_id']);
                $bookingsByProforma[$proformaId][] = $bookingRow;
            }
        }

        foreach ($rows as &$row) {
            $row['bookings'] = $bookingsByProforma[(int)$row['id']] ?? [];
            $row['canceledby'] = isset($row['cancelled_by']) ? (int)$row['cancelled_by'] : null;
        }
        unset($row);

        return $this->respond([
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => (int)ceil($total / $perPage),
            ]
        ]);
    }

    /* =========================
       SHOW PROFORMA
    ==========================*/
    public function show($id = null)
    {
        $db = db_connect();

        $proforma = $db->table('proformas')->where('id', $id)->get()->getRowArray();
        if (!$proforma) {
            return $this->failNotFound('Proforma not found');
        }

        $items = $db->table('proforma_items')
            ->where('proforma_id', $id)
            ->orderBy('sort_order')
            ->get()->getResultArray();

        $bookings = $db->table('proforma_bookings pb')
            ->select('b.id, b.reference, b.check_in, b.check_out')
            ->join('bookings b', 'b.id = pb.booking_id')
            ->where('pb.proforma_id', $id)
            ->get()->getResultArray();

        return $this->respond([
            'proforma' => $proforma,
            'items' => $items,
            'bookings' => $bookings
        ]);
    }

    /* =========================
       SUGGEST FROM BOOKING
    ==========================*/
    public function suggest()
    {
        $bookingId = (int)$this->request->getGet('booking_id');
        if (!$bookingId) {
            return $this->failValidationError('booking_id required');
        }

        $db = db_connect();

        $booking = $db->table('bookings')->where('id', $bookingId)->get()->getRowArray();
        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        return $this->respond([
            'customer_id' => $booking['customer_id'],
            'invoice_date' => date('Y-m-d'),
            'currency' => 'USD',
            'bookings' => [$bookingId],
            'items' => [
                [
                    'description' => 'Room stay (' . $booking['check_in'] . ' to ' . $booking['check_out'] . ')',
                    'qty' => 1,
                    'unit_type' => 'per_booking',
                    'unit_price' => 0,
                    'line_total' => 0
                ]
            ]
        ]);
    }

    /* =========================
       UNINVOICED BOOKINGS
    ==========================*/
    public function uninvoicedBookings($customerId)
    {
        $db = db_connect();

        $rows = $db->table('bookings b')
            ->select('b.id, b.reference, b.check_in, b.check_out')
            ->where('b.customer_id', $customerId)
            ->where('NOT EXISTS (
                SELECT 1 FROM proforma_bookings pb WHERE pb.booking_id = b.id
            )')
            ->orderBy('b.check_in')
            ->get()->getResultArray();

        return $this->respond($rows);
    }

    /* =========================
       CREATE PROFORMA
    ==========================*/
    public function create()
    {
        $data = $this->request->getJSON(true);
        $db = db_connect();
        $db->transStart();
        
        $loggedBy = $this->currentEmployeeId();

        $proformaNo = 'PF-' . date('Y') . '-' . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);

        $db->table('proformas')->insert([
            'proforma_no' => $proformaNo,
            'customer_id' => $data['customer_id'],
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'] ?? null,
            'currency' => $data['currency'],
            'subtotal' => $data['subtotal'],
            'total' => $data['total'],
            'notes' => $data['notes'] ?? '',
            'created_by' => $loggedBy
        ]);

        $pid = $db->insertID();

        foreach ($data['bookings'] as $bid) {
            $db->table('proforma_bookings')->insert([
                'proforma_id' => $pid,
                'booking_id' => $bid
            ]);
        }
        
        //print_r($data['items']);

        foreach ($data['items'] as $i => $it) {
            $db->table('proforma_items')->insert([
                'proforma_id' => $pid,
                'description' => $it['description'],
                'qty' => $it['qty'],
                'unit_type' => $it['unit_type'],
                'unit_price' => $it['unit_price'],
                'line_total' => ($it['qty'] * $it['unit_price']),
                'sort_order' => $i + 1
            ]);
            
           //echo  $db->getLastQuery();
        }

        $db->transComplete();

        return $this->respondCreated(['status' => 'success', 'id' => $pid]);
    }
    
    public function update($id = null)
    {
        $db = \Config\Database::connect();
        
        $loggedBy = $this->currentEmployeeId();
        $id = (int) $id;

       $data = $this->request->getJSON(true);
        $items = $data['items'] ?? [];

        if ($id < 1) return $this->failValidationError('Invalid proforma ID.');
        if (empty($items)) return $this->failValidationError('Items are required.');

        $db->transStart();

        // 1) Compute totals
        $grandTotal = 0;
         foreach ($items as &$i) {
            $unit = (float) ($i['unit_price'] ?? 0);
            $qty  = (float) ($i['qty'] ?? 0);
            $i['line_total'] = round($unit * $qty, 2);
            $grandTotal += $i['line_total'];
        }
        unset($i);

        // 2) Update header
        $db->table('proformas')->where('id', $id)->update([
            'customer_id'  => (int) ($data['customer_id'] ?? 0),
            'invoice_date' => $data['invoice_date'] ?? date('Y-m-d'),
            'due_date'     => $data['due_date'] ?? null,
            'currency'     => $data['currency'] ?? 'USD',
            'total'        => round($grandTotal, 2),
            'updated_at'   => date('Y-m-d H:i:s'),
            'updated_by' => $loggedBy
        ]);

        // 3) Replace items
       $db->table('proforma_items')->where('proforma_id', $id)->delete();

        foreach ($items as $i) {
            $db->table('proforma_items')->insert([
                'proforma_id' => $id,
                'description' => $i['description'] ?? '',
                'unit_price'  => (float) ($i['unit_price'] ?? 0),
                'qty'       => (float) ($i['qty'] ?? 0),
                'unit_type'   => $i['unit_type'] ?? 'per_night',
                'line_total'  => (float) ($i['qty'] * $i['unit_price']),
            ]);
        }

         // 4) Replace booking links (optional)
        if (isset($data['booking_ids'])) {
            // remove old links
            $old = $db->table('proforma_bookings')->select('booking_id')->where('proforma_id', $id)->get()->getResultArray();
            
            $oldIds = array_map(function($r) {
                return (int)$r['booking_id'];
            }, $old);

            $db->table('proforma_bookings')->where('proforma_id', $id)->delete();

            // clear old bookings.proforma_id (optional)
            if (!empty($oldIds)) {
                $db->table('bookings')->whereIn('id', $oldIds)->update(['proforma_id' => null]);
            }

            // add new
            foreach ((array)$data['booking_ids'] as $bid) {
                $bid = (int)$bid;
                if ($bid < 1) continue;

                $db->table('proforma_bookings')->insert([
                    'proforma_id' => $id,
                    'booking_id'  => $bid
                ]);

                $db->table('bookings')->where('id', $bid)->update(['proforma_id' => $id]);
            }
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->failServerError('Failed to update proforma.');
        }

        return $this->respond(['status' => 'success']);
    }

    
    public function cancel($id = null)
    {
        $id = (int) $id;
        if ($id < 1) {
            return $this->failValidationError('Invalid proforma id');
        }

        $db = \Config\Database::connect();

        $proforma = $db->table('proformas')->where('id', $id)->get()->getRowArray();
        if (! $proforma) {
            return $this->failNotFound('Proforma not found');
        }

        $data = $this->request->getJSON(true);
        if (! is_array($data)) {
            $data = $this->request->getPost() ?? [];
        }

        $cancelReason = trim((string) ($data['cancel_reason'] ?? ''));
        if ($cancelReason === '') {
            return $this->failValidationError('cancel_reason is required');
        }

        if (($proforma['status'] ?? '') === 'cancelled' || ! empty($proforma['cancelled_at'])) {
            return $this->respond([
                'status' => 'success',
                'message' => 'Proforma already cancelled',
                'data' => [
                    'id' => $id,
                    'cancel_reason' => $proforma['cancel_reason'] ?? null,
                    'cancelled_at' => $proforma['cancelled_at'] ?? null,
                    'cancelled_by' => isset($proforma['cancelled_by']) ? (int) $proforma['cancelled_by'] : null,
                ],
            ]);
        }

        foreach (['cancel_reason', 'cancelled_by', 'cancelled_at'] as $field) {
            if (! $db->fieldExists($field, 'proformas')) {
                return $this->failServerError('Required field missing in proformas table: ' . $field);
            }
        }

        $cancelledBy = $this->currentEmployeeId();
        if ($cancelledBy === null && isset($data['cancelled_by']) && is_numeric($data['cancelled_by'])) {
            $cancelledBy = (int) $data['cancelled_by'];
        }

        if ($cancelledBy === null) {
            return $this->failUnauthorized('Unable to determine cancelling employee');
        }

        $updateData = [
            'cancel_reason' => $cancelReason,
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => $cancelledBy,
        ];

        if ($db->fieldExists('status', 'proformas')) {
            $updateData['status'] = 'cancelled';
        }

        if ($db->fieldExists('updated_at', 'proformas')) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($db->fieldExists('updated_by', 'proformas')) {
            $updateData['updated_by'] = $cancelledBy;
        }

        $updated = $db->table('proformas')->where('id', $id)->update($updateData);
        if (! $updated) {
            return $this->failServerError('Unable to cancel proforma');
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Proforma cancelled successfully',
            'data' => array_merge(['id' => $id], $updateData),
        ]);
    }

    public function viewPdf($id = 0){
        // 1. Create instance
       $id = (int)$id;
        if ($id < 1) {
            return $this->failValidationError('Invalid proforma id');
        }

        helper('number_to_words');

        $db = \Config\Database::connect();

        // 1) Fetch proforma header
        $proforma = $db->table('proformas p')
            ->select('p.*, customers.name AS customer_name, customers.email AS customer_email')
            ->join('customers', 'customers.id = p.customer_id', 'left')
            ->where('p.id', $id)
            ->get()
            ->getRowArray();

        if (!$proforma) {
            return $this->failNotFound('Proforma not found');
        }

        // 2) Fetch items
        $items = $db->table('proforma_items')
            ->where('proforma_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        // 3) Fetch linked booking details for proforma
        $bookings = $db->table('proforma_bookings pb')
            ->select("pb.id,
                pb.proforma_id,
                pb.booking_id,
                b.reference AS booking_no,
                DATE(b.created_at) AS booking_date,
                (
                    SELECT bg.name
                    FROM booking_guests bg
                    WHERE bg.booking_id = b.id AND bg.is_primary = 1
                    ORDER BY bg.id ASC
                    LIMIT 1
                ) AS primary_pax_name,
                rt.name AS booked_room_category,
                b.check_in,
                b.check_out,
                DATEDIFF(b.check_out, b.check_in) AS no_of_nights,
                b.guests AS pax", false)
            ->join('bookings b', 'b.id = pb.booking_id', 'left')
            ->join('room_types rt', 'rt.id = b.room_type_id', 'left')
            ->where('pb.proforma_id', $id)
            ->orderBy('pb.id', 'ASC')
            ->get()
            ->getResultArray();

        // 4) Fetch active default bank account
        $defaultBankAccount = $db->table('bank_accounts')
            ->where('is_default', 1)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        // 5) Resolve assets for PDF
        $logoFile = FCPATH . 'logo.png';
        $sealFile = FCPATH . 'seal.jpg';

        $logoPath = is_file($logoFile) ? str_replace('\\', '/', $logoFile) : null;
        $sealPath = is_file($sealFile) ? str_replace('\\', '/', $sealFile) : null;

        // 6) Generate filename
        // If you have a proforma number field, use it. Else fallback:
        $proformaNo = $proforma['proforma_no'] ?? ('PF-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT));
        $safeNo = preg_replace('/[^A-Za-z0-9\-_]/', '-', $proformaNo);
        $fileName = $safeNo . '.pdf';

        // 7) Create folder if not exists
        $publicDir = rtrim(FCPATH, '/\\'); // points to /public
        $saveDir = $publicDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'proformas';

        if (!is_dir($saveDir)) {
            @mkdir($saveDir, 0755, true);
        }

        $filePath = $saveDir . DIRECTORY_SEPARATOR . $fileName;

        // 8) Build HTML (simple inline template)
        $html = view('templates/proforma_pdf', [
            'p' => $proforma,
            'items' => $items,
            'bookings' => $bookings,
            'defaultBankAccount' => $defaultBankAccount,
            'proformaNo' => $proformaNo,
            'logoPath' => $logoPath,
            'sealPath' => $sealPath,
        ]);

        // 9) Generate PDF with mPDF
        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
            ]);

            $mpdf->SetTitle('Proforma ' . $proformaNo);
            $mpdf->WriteHTML($html);
            $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);
        } catch (\Throwable $e) {
            return $this->failServerError('PDF generation failed: ' . $e->getMessage());
        }

        // 10) Return URL
        $baseUrl = rtrim(base_url(), '/');
        $fileUrl = $baseUrl . '/uploads/proformas/' . $fileName;

        return $this->respond([
            'status' => 'success',
            'url' => $fileUrl,
            'file_name' => $fileName,
        ]);
    }
}








