<?php
declare(strict_types=1);

namespace App\Support;

final class Pagination
{
    /** @return array{limit:int, offset:int, page:int} */
    public static function fromQuery(array $query, int $defaultLimit = 20, int $maxLimit = 50): array
    {
        $page = isset($query['page']) ? max(1, (int)$query['page']) : 1;
        $limit = isset($query['limit']) ? (int)$query['limit'] : $defaultLimit;
        $limit = max(1, min($maxLimit, $limit));
        $offset = ($page - 1) * $limit;
        return ['limit' => $limit, 'offset' => $offset, 'page' => $page];
    }
}
