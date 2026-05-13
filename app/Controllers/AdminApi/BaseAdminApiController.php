<?php
declare(strict_types=1);

namespace App\Controllers\AdminApi;

use App\Support\ApiResponse;

abstract class BaseAdminApiController
{
    protected function notImplemented(): \App\Http\Response
    {
        return ApiResponse::error('NOT_IMPLEMENTED', 'Not implemented yet', 501);
    }
}
