<?php


namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Cors implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Handle preflight (OPTIONS) early
        if ($request->getMethod() === 'options') {
            $response = service('response');

            $this->addCorsHeaders($request, $response);

            // 204 No Content is good for preflight
            $response->setStatusCode(204);

            return $response; // stop here, no controller
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add CORS headers to all normal responses
        $this->addCorsHeaders($request, $response);

        return $response;
    }

    private function addCorsHeaders(RequestInterface $request, ResponseInterface $response)
    {
        // Adjust to your frontend origins (Vite, etc.)
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigins = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];

        if ($origin && in_array($origin, $allowedOrigins, true)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        } else {
            // For testing only; in production be strict
            $response->setHeader('Access-Control-Allow-Origin', '*');
        }

        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }
}

