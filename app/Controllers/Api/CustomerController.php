<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Services\CustomerService;

/**
 * Handles customer listing and profile API endpoints.
 *
 * GET /api/customers
 * GET /api/customers/{id}
 */
class CustomerController extends Controller
{
    /**
     * GET /api/customers
     *
     * Returns a paginated list of customers.
     * Supports query params: ?page=1&per_page=20
     */
    public function index(): never
    {
        $page    = max(1, (int) $this->request->query('page', '1'));
        $perPage = max(1, (int) $this->request->query('per_page', '20'));

        Response::json((new CustomerService())->getPaginated($page, $perPage));
    }

    /**
     * GET /api/customers/{id}
     *
     * Returns the full profile of a single customer:
     * customer info, last 10 events, aggregated statistics.
     *
     * @param string $id URL segment — validated as a positive integer.
     */
    public function show(string $id): never
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            Response::error('Customer ID must be a positive integer.', 400);
        }

        Response::json((new CustomerService())->getProfile((int) $id));
    }
}
