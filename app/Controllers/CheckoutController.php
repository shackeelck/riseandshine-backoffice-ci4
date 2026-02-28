<?php

namespace App\Controllers;


class CheckoutController extends BaseApiController
{
    protected $format = 'json';

    // GET /api/checkout/{bookingId}
    public function show($bookingId = null)
    {
        $db = \Config\Database::connect();

        // booking + joins
        $booking = $db->table('bookings b')
            ->select('b.*, customers.name AS customer_name, room_types.name AS room_type, room_inventory.room_number')
            ->join('customers', 'customers.id = b.customer_id', 'left')
            ->join('room_types', 'room_types.id = b.room_type_id', 'left')
            ->join('room_inventory', 'room_inventory.id = b.room_inventory_id', 'left')
            ->where('b.id', (int)$bookingId)
            ->get()->getRowArray();

        if (!$booking) return $this->failNotFound('Booking not found');

        // guests
        $guests = $db->table('booking_guests')
            ->where('booking_id', (int)$bookingId)
            ->orderBy('is_primary', 'DESC')
            ->get()->getResultArray();

        // minibar master
        $minibarItems = $db->table('minibar_items')
            ->select('id, name, price')
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        // minibar consumption for booking (booking_minibar table)
        $minibarLines = $db->table('booking_minibar bmi')
            ->select('bmi.*, mi.name AS item_name')
            ->join('minibar_items mi', 'mi.id = bmi.minibar_item_id', 'left')
            ->where('bmi.booking_id', (int)$bookingId)
            ->orderBy('bmi.id', 'ASC')
            ->get()->getResultArray();

        // proforma (optional)
        // NOTE: Your previous join was incorrect: proformas.id = proforma_bookings.booking_id
        // It should usually be proformas.id = proforma_bookings.proforma_id
        $proforma = $db->table('proforma_bookings pb')
            ->select('p.*')
            ->join('proformas p', 'p.id = pb.proforma_id', 'left')
            ->where('pb.booking_id', (int)$bookingId)
            ->get()->getRowArray();

        return $this->respond([
            'booking'        => $booking,
            'guests'         => $guests,
            'proforma'       => $proforma,
            'minibar_items'  => $minibarItems,
            'minibar_lines'  => $minibarLines,
        ]);
    }

    /**
     * ✅ Option B: Auto-save consumed qty per row (debounced from frontend)
     * POST /api/checkout/{bookingId}/minibar/consume
     * Body: { line_id: int, consumed: int }
     */
    public function consumeMinibar($bookingId = null)
    {
        $db = \Config\Database::connect();
        $bookingId = (int)$bookingId;

        $payload = $this->request->getJSON(true);
        $lineId  = (int)($payload['line_id'] ?? 0);
        $consumed = $payload['consumed'] ?? null;

        if ($bookingId <= 0) return $this->failValidationError('Invalid booking id');
        if ($lineId <= 0) return $this->failValidationError('line_id is required');
        if ($consumed === null || $consumed === '') return $this->failValidationError('consumed is required');

        $consumed = (int)$consumed;

        // Ensure booking exists
        $booking = $db->table('bookings')->where('id', $bookingId)->get()->getRowArray();
        if (!$booking) return $this->failNotFound('Booking not found');

        // Get minibar line, ensure it belongs to booking
        $line = $db->table('booking_minibar')
            ->where('id', $lineId)
            ->where('booking_id', $bookingId)
            ->get()->getRowArray();

        if (!$line) return $this->failNotFound('Minibar line not found for this booking');

        $loadedQty = (int)($line['quantity'] ?? 0);
        $unitPrice = (float)($line['unit_price'] ?? 0);

        // Clamp consumed 0..loadedQty
        if ($consumed < 0) $consumed = 0;
        if ($consumed > $loadedQty) $consumed = $loadedQty;

        $total = $consumed * $unitPrice;

        $db->transStart();

        $db->table('booking_minibar')
            ->where('id', $lineId)
            ->where('booking_id', $bookingId)
            ->update([
                'consumed'   => $consumed,
                'total'      => $total,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->failServerError('Failed to save minibar consumption');
        }

        return $this->respond([
            'status' => 'success',
            'line' => [
                'id' => $lineId,
                'consumed' => $consumed,
                'total' => $total,
            ]
        ]);
    }

    // POST /api/checkout/{bookingId}
    public function complete($bookingId = null)
    {
        $db = \Config\Database::connect();
        $bookingId = (int)$bookingId;
        
        $loggedBy = $this->currentEmployeeId();

        $data = $this->request->getJSON(true);

        $booking = $db->table('bookings')->where('id', $bookingId)->get()->getRowArray();
        if (!$booking) return $this->failNotFound('Booking not found');

        // Only allow checkout if checked_in
        if (($booking['status'] ?? '') !== 'checked_in') {
            return $this->failValidationError('Booking is not checked-in. Cannot checkout.');
        }

        $payment = $data['payment'] ?? [];
        $checkoutRemarks = $data['checkout_remarks'] ?? null;

        $db->transStart();

        // ✅ If you want: mark minibar rows as "posted" on checkout (optional)
        // $db->table('booking_minibar')->where('booking_id', $bookingId)->update(['status' => 'posted']);

        // Update proforma payment status (if exists)
        if (!empty($payment['proforma_id'])) {
            $proformaId = (int)$payment['proforma_id'];

            $db->table('proformas')->where('id', $proformaId)->update([
                'payment_status' => $payment['payment_status'] ?? 'unpaid',
                'paid_amount'    => (float)($payment['paid_amount'] ?? 0),
                'payment_method' => $payment['payment_method'] ?? null,
                'payment_ref'    => $payment['payment_ref'] ?? null,
                'payment_date'   => !empty($payment['payment_date']) ? $payment['payment_date'] : null,
                
            ]);
        }

        // Set booking checked_out
        $db->table('bookings')->where('id', $bookingId)->update([
            'status'          => 'checked_out',
            'actual_check_out'=> date('Y-m-d H:i:s'),
            'checked_out_by'    => $loggedBy ?? null,
            'checkout_remarks'=> $checkoutRemarks,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->failServerError('Checkout failed');
        }

        return $this->respond(['status' => 'success']);
    }
}
