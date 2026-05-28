<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Event;
use App\Exceptions\NotFoundException;
use App\Services\CacheService;

/**
 * Customer retrieval and profile assembly service.
 */
class CustomerService
{
    private Customer      $customerModel;
    private Event         $eventModel;
    private CacheService  $cache;

    public function __construct()
    {
        $this->customerModel = new Customer();
        $this->eventModel    = new Event();
        $this->cache         = new CacheService();
    }

    /**
     * Returns a full customer profile.
     *
     * Profile includes:
     *  - customer : basic info (id, email, name, timestamps)
     *  - recent_events : last 10 events with their properties
     *  - stats : events_by_type breakdown + total_spend
     *
     * @param int $id Customer primary key.
     * @return array
     * @throws NotFoundException If no customer with that ID exists.
     */
    public function getProfile(int $id): array
    {
        return $this->cache->remember("customer_{$id}", 60, function () use ($id) {
            $customer = $this->customerModel->findById($id);

            if ($customer === null) {
                throw new NotFoundException("Customer #{$id} not found.");
            }

            return [
                'customer'      => $customer,
                'recent_events' => $this->eventModel->findLastByCustomer($id, 10),
                'stats'         => $this->eventModel->getStatsByCustomer($id),
            ];
        });
    }

    /**
     * Returns a paginated list of customers, newest first.
     *
     * @param int $page    1-based page number.
     * @param int $perPage Records per page (capped at 100).
     * @return array{data: array, total: int, page: int, per_page: int, last_page: int}
     */
    public function getPaginated(int $page = 1, int $perPage = 20): array
    {
        $page    = max(1, $page);
        $perPage = min(100, max(1, $perPage));

        return $this->cache->remember("customers_p{$page}_{$perPage}", 60, function () use ($page, $perPage) {
            $offset = ($page - 1) * $perPage;
            $total  = $this->customerModel->count();

            return [
                'data'      => $this->customerModel->findAll($perPage, $offset),
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => (int) ceil($total / $perPage),
            ];
        });
    }
}
