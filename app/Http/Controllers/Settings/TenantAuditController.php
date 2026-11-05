<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TenantAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantAuditController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = $this->query($request)->paginate(50)->withQueryString();

        return Inertia::render('settings/Audit', [
            'logs' => $logs,
            'filters' => $request->only(['event', 'user_id', 'date_from', 'date_to']),
            'events' => TenantAuditLog::query()->distinct()->orderBy('event')->pluck('event'),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'auditoria-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($request): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Fecha', 'Usuario', 'Evento', 'Entidad', 'ID', 'IP']);

            $this->query($request)->chunk(500, function ($logs) use ($handle): void {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->created_at?->toDateTimeString(),
                        $log->user?->email,
                        $log->event,
                        $log->subject_type,
                        $log->subject_id,
                        $log->ip,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function query(Request $request): Builder
    {
        return TenantAuditLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('event'), fn (Builder $query) => $query->where('event', $request->get('event')))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->get('user_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->get('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->get('date_to')))
            ->orderByDesc('created_at');
    }
}
