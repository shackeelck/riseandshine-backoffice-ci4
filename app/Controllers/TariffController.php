<?php
namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\TariffModel;
use App\Models\TariffPeriodModel;
use App\Models\TariffSupplementModel;
use App\Models\TariffTransferModel;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;

class TariffController extends ResourceController
{
    protected $format = 'json';
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // GET /api/tariffs
    public function index()
    {
        $tariff = new TariffModel();
        $data = $tariff->withChildren()->findAll();
        return $this->respond($data);
    }

    // GET /api/tariffs/{id}
    public function show($id = 0)
    {
        
        $tariff = new TariffModel();
        $row = $tariff->find($id);
        if (!$row) return $this->failNotFound('Tariff not found');

        // load children
        $periods    = (new TariffPeriodModel())->where('tariff_id',$id)->findAll();
        $supps      = (new TariffSupplementModel())->where('tariff_id',$id)->findAll();
        $transfers  = (new TariffTransferModel())->where('tariff_id',$id)->findAll();

        return $this->respond([
            'tariff'     => $row,
            'periods'    => $periods,
            'supplements'=> $supps,
            'transfers'  => $transfers
        ]);
    }

    // POST /api/tariffs
    public function create()
    {
        $data = $this->request->getJSON(true);
        $tariffModel     = new TariffModel();
        $periodModel     = new TariffPeriodModel();
        $suppModel       = new TariffSupplementModel();
        $transferModel   = new TariffTransferModel();

        $this->db->transStart();

        try {
            // Insert master
            $tariffId = $tariffModel->insert([
                'room_type_id' => $data['room_type_id']
            ]);

            // Insert periods
            foreach ($data['periods'] as $p) {
                $periodModel->insert([
                    'tariff_id'   => $tariffId,
                    'start_date'  => $p['start'],
                    'end_date'    => $p['end'],
                    'single_rate' => $p['single'],
                    'double_rate' => $p['double'],
                    'extra_rate'  => $p['extra']
                ]);
            }

            // Insert supplements
            foreach ($data['supplements'] as $m) {
                $suppModel->insert([
                    'tariff_id' => $tariffId,
                    'name'      => $m['name'],
                    'price'     => $m['price']
                ]);
            }

            // Insert transfers
            foreach ($data['transfers'] as $t) {
                $transferModel->insert([
                    'tariff_id' => $tariffId,
                    'type'      => $t['type'],
                    'price'     => $t['price']
                ]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new DatabaseException('Transaction failed');
            }
            return $this->respondCreated(['status'=>'success','id'=>$tariffId]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->failServerError($e->getMessage());
        }
    }

    // PUT /api/tariffs/{id}
    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $tariffModel    = new TariffModel();
        $periodModel    = new TariffPeriodModel();
        $suppModel      = new TariffSupplementModel();
        $transferModel  = new TariffTransferModel();

        $this->db->transStart();

        try {
            // Update master
            $tariffModel->update($id, ['room_type_id'=>$data['room_type_id']]);
            
            
            
            // Clear existing children
            $periodModel->where('tariff_id',$id)->delete();
            $suppModel->where('tariff_id',$id)->delete();
            $transferModel->where('tariff_id',$id)->delete();
            
            // return $this->respond($data['periods']);
            
            // Re-insert children
            foreach ($data['periods'] as $p) {
                
               $periodModel->insert([
                    'tariff_id'   => $id,
                    'start_date'  => $p['start_date'],
                    'end_date'    => $p['end_date'],
                    'single_rate' => $p['single_rate'],
                    'double_rate' => $p['double_rate'],
                    'extra_rate'  => $p['extra_rate']
                ]);
                
               
            }
            
            
            foreach ($data['supplements'] as $m) {
                 $suppModel->insert([
                    'tariff_id' => $id,
                    'name'      => $m['name'],
                    'price'     => $m['price']
                ]);
            }
            foreach ($data['transfers'] as $t) {
                $transferModel->insert([
                    'tariff_id' => $id,
                    'type'      => $t['type'],
                    'price'     => $t['price']
                ]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new DatabaseException('Transaction failed');
            }
            return $this->respond(['status'=>'success']);
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->failServerError($e->getMessage());
        }
    }

    // DELETE /api/tariffs/{id}
    public function delete($id = null)
    {
        (new TariffModel())->delete($id);
        return $this->respondDeleted(['status'=>'deleted']);
    }
}
