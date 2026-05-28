<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Services\SegmentService;
use App\Validators\SegmentValidator;

/**
 * Handles the segmentation query API endpoint.
 *
 * POST /api/segments/query
 */
class SegmentController extends Controller
{
    /**
     * Executes a segmentation query and returns matching customers.
     *
     * The query uses EXISTS subqueries with indexed column lookups —
     * see SegmentService for the full query-building strategy.
     */
    public function query(): never
    {
        $conditions = (new SegmentValidator())->validate($this->request->all());
        $customers  = (new SegmentService())->query($conditions);

        Response::json([
            'count'     => count($customers),
            'customers' => $customers,
        ]);
    }
}
