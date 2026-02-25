<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\GuestModel;

class Register extends BaseController
{
    
    private $API_TOKEN = 'RiSnShnHOTMV@2026'; // Replace with your actual token

    public function create()
    {
        
        
        //$authHeader = $this->request->getHeaderLine('Authorization');
        
        /*if (!$this->isAuthorized($authHeader)) { 
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }*/
        
        /*Json Type Submission */
        
        //$json = $this->request->getJSON(true);
        /*if (!$json) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid Request' ]);
        }*/

        $primaryData = $this->request->getPost('primary');
        $extraData = $this->request->getPost('extraGuests');
        $json = json_decode($primaryData,true);
        
        $xtra_json = json_decode($extraData,true);
        
        //$signature = $this->request->getFile('signature');
        $filename = '';
        $dataUrl = $this->request->getPost('signature');
        if(isset($dataUrl) and $dataUrl != '') {
            if (!preg_match('#^data:image/(\w+);base64,(.+)$#', $dataUrl, $matches)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid image format'
                ]);
            }
        
        
            [$mime, $type, $base64] = [$matches[0], $matches[1], $matches[2]];
            $ext = strtolower($type); // e.g. png, jpeg, jpg
            $decoded = base64_decode($base64);
        
        
            if ($decoded === false) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Base64 decode failed'
                ]);
            }
        
        
            $filename = uniqid('sig_', true) . '.' . $ext;
            $path = WRITEPATH . "uploads/{$filename}";

            if (file_put_contents($path, $decoded) === false) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to save image'
                ]);
            }
        }

        
        /*if ($signature && $signature->isValid()) {
            $newName = $signature->getRandomName();
            $signature->move(WRITEPATH . 'uploads', $newName);

            $json['signature']  = $newName;

            //return $this->response->setJSON(['status' => 'success']);
        } else {
            //$code = $signature->getError();              // integer error code
            $msg  = $signature->getErrorString();
            
            return $this->response->setJSON(['status' => 'error', 'message' => "Invalid signature. {$msg}"]);
        }*/
        
        
        // exit;
        
        // print_r($post);

        $guestData = [
            'guest_given_name'  => $json['firstName'] ?? '',
            'guest_sur_name'    => $json['surname'] ?? '',
            'guest_nationality' => $json['country'] ?? '',
            'guest_contact_no'  => $json['contact'] ?? '',
            'guest_email'       => $json['email'] ?? '',
            'document_type'     => $json['docType'] ?? 0,
            'document_no'       => $json['docNumber'] ?? '',
            'signature'         => $filename ?? ''
            
        ];
        
        //print_r($guestData);

        $model = new GuestModel();
        if ($model->insert($guestData)) {
            $id = $model->getInsertID();
            if(!empty($xtra_json)){
                
                $ExtraGuestModel = new \App\Models\UserGroupModel();
                
                 $xtr_guestData = [
                    'main_guestid'  => $id,
                    'pax_name'      => $xtra_json['name'] ?? '',
                    'nationality'   => $xtra_json['nat'] ?? '',
                    'date_of_birth' => $xtra_json['dob'] ?? '',
                    'document_type' => $xtra_json['docType'] ?? 0,
                    'document_no'   => $xtra_json['docNumber'] ?? '',

                ];
                
                $userGroupModel->insert($xtr_guestData);
            }
           
            
            return $this->response->setJSON(['status' => 'success', 'id' => $id,'signature'=>$filename]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Registration Faild']);
    }

    private function isAuthorized($header)
    {
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return false;
        }

        $token = trim(str_replace('Bearer', '', $header));
        return $token === $this->API_TOKEN;
    }
}
