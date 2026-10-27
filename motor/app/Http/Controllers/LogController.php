<?php

namespace App\Http\Controllers;

use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Http\Requests\LogListRequest;
use App\Http\Resources\IntegrationLogResource;
use App\Http\Responses\PaginatedResponse;
use App\Models\IntegrationLog;
use Carbon\CarbonImmutable;

class LogController extends Controller
{
    /**
     * Newest first. The filters combine with "and"; the contract values are
     * translated to the ones stored in the database.
     */
    public function index(LogListRequest $request)
    {
        $paginator = IntegrationLog::query()
            ->when($request->query('sistema'), fn ($q, $v) => $q->where('system', LogSystem::fromContract($v)))
            ->when($request->query('estado'), fn ($q, $v) => $q->where('status', LogStatus::fromContract($v)))
            ->when($request->query('direccion'), fn ($q, $v) => $q->where('direction', LogDirection::fromContract($v)))
            ->when($request->query('correlacion'), fn ($q, $v) => $q->where('correlation_id', $v))
            ->when($request->query('desde'), fn ($q, $v) => $q->where('logged_at', '>=', $this->toUtc($v)))
            ->when($request->query('hasta'), fn ($q, $v) => $q->where('logged_at', '<=', $this->toUtc($v)))
            ->orderByDesc('logged_at')
            ->orderByDesc('id')
            ->paginate($request->perPage(), ['*'], 'pagina', $request->page());

        return PaginatedResponse::make(
            $paginator->withQueryString(),
            fn (IntegrationLog $log) => IntegrationLogResource::make($log)->resolve(),
        );
    }

    // Dates are stored in UTC, so a date with another offset is converted first.
    private function toUtc(string $value): string
    {
        return CarbonImmutable::parse($value)->utc()->toDateTimeString();
    }
}
