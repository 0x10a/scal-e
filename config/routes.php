<?php
declare(strict_types=1);

use App\Controllers\Api\EventController;
use App\Controllers\Api\CustomerController;
use App\Controllers\Api\SegmentController;
use App\Controllers\Web\DashboardController;

// ── API Routes (protected — require X-Api-Key header or ?api_key= param) ────
$router->post('/api/events',          EventController::class,    'store',  protected: true);
$router->get('/api/customers',        CustomerController::class, 'index',  protected: true);
$router->get('/api/customers/{id}',   CustomerController::class, 'show',   protected: true);
$router->post('/api/segments/query',  SegmentController::class,  'query',  protected: true);

// ── Web Routes ──────────────────────────────────────────────
$router->get('/',           DashboardController::class, 'index');
$router->get('/customers',  DashboardController::class, 'customers');
$router->get('/segments',   DashboardController::class, 'segments');
