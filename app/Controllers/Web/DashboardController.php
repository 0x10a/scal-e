<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Services\CustomerService;

/**
 * Handles the optional web UI routes.
 *
 * GET /
 * GET /customers
 * GET /segments
 */
class DashboardController extends Controller
{
    /**
     * GET /
     * Main dashboard — quick overview.
     */
    public function index(): never
    {
        $this->render('dashboard/index');
    }

    /**
     * GET /customers
     * Paginated customer list.
     */
    public function customers(): never
    {
        $page   = max(1, (int) $this->request->query('page', '1'));
        $result = (new CustomerService())->getPaginated($page, 20);

        $this->render('customers/index', $result);
    }

    /**
     * GET /segments
     * Interactive segment query playground.
     */
    public function segments(): never
    {
        $this->render('segments/index');
    }
}
