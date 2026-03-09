<?php

namespace App\Modules\History\Application;

use App\Modules\Chat\Infrastructure\ChatHistoryRepository;

class HistoryService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ChatHistoryRepository();
    }

    public function paginated(array $filters)
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(10, min(100, (int) ($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $normalized = [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'ip' => $filters['ip'] ?? null,
            'mode' => $filters['mode'] ?? null,
            'start_date' => $filters['start_date'] ?? date('Y-m-d', strtotime('-30 days')),
            'end_date' => $filters['end_date'] ?? date('Y-m-d'),
        ];

        $rows = $this->repository->filteredList($normalized);
        $total = $this->repository->countFiltered($normalized);

        return [
            'items' => $rows,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => max(1, (int) ceil($total / $limit)),
            'start_date' => $normalized['start_date'],
            'end_date' => $normalized['end_date'],
            'ip' => $normalized['ip'],
            'mode' => $normalized['mode'],
        ];
    }

    public function stats($startDate, $endDate)
    {
        return $this->repository->statistics($startDate, $endDate);
    }
}
