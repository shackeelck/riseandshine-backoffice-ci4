<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['url', 'form'];
    
    protected $session;

    /**
     * Constructor.
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        
        $this->session = session();
        
        
        // Apply auth check for all routes except login/auth
        $uri = current_url();
        /*$excludedRoutes = [
            base_url('/auth'),
            base_url('/auth/logout')
        ];
        
        
        if (!in_array($uri, $excludedRoutes) && !$this->session->get('logged_in')) {
            
            //echo 43434;
            //return redirect()->to('/auth/')->send();
        }*/

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
    }
    
    
    protected function requireAdmin()
    {
        if ($this->session->get('role') !== 'admin') {
            // Redirect non-admin users
            return redirect()->to('/dashboard')->with('error', 'Access denied. Admins only.')->send();
        }
    }
    
    protected function requireStaff()
    {
        if ($this->session->get('role') !== 'admin' && $this->session->get('role') !== 'staff') {
            // Redirect non-admin users
           
            return redirect()->to('/dashboard')->with('error', 'Access denied. Admins & Staffs only.')->send();
        }
    }
}
