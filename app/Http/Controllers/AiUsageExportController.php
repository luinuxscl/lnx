<?php

namespace App\Http\Controllers;

use App\Actions\ListAiUsage;
use App\Enums\Role;
use App\Models\AiUsageEvent;
use App\Models\Setting;
use App\Support\Appearance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiUsageExportController extends Controller
{
    /**
     * Stream the filtered AI consumption record as a CSV download.
     */
    public function __invoke(): StreamedResponse
    {
        abort_unless(
            auth()->user()?->hasRole(Role::Root) && Gate::allows('viewAny', Setting::class),
            403,
        );

        try {
            $events = app(ListAiUsage::class)->export([
                'user' => (string) request()->query('user', ''),
                'application' => (string) request()->query('application', ''),
                'model' => (string) request()->query('model', ''),
                'status' => (string) request()->query('status', ''),
                'from' => (string) request()->query('from', ''),
                'to' => (string) request()->query('to', ''),
            ]);
        } catch (ValidationException) {
            abort(422);
        }

        $timezone = Appearance::currentTimezone();
        $filename = 'ai-usage-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($events, $timezone): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                __('Date'),
                __('User'),
                __('Email'),
                __('Application'),
                __('Model'),
                __('Tokens'),
                __('Cost'),
                __('Cost source'),
                __('Status'),
                __('Error'),
                __('Duration'),
                __('Attempt'),
            ]);

            foreach ($events as $event) {
                $status = (string) __(Str::headline($event->status));
                $error = $event->error_category !== null
                    ? (string) __(Str::headline($event->error_category))
                    : null;

                fputcsv($handle, [
                    $event->recorded_at->timezone($timezone)->format('d/m/y H:i'),
                    $event->user_name,
                    $event->user_email,
                    $event->application,
                    $event->effective_model ?? $event->requested_model,
                    $event->total_tokens,
                    $event->reported_cost ?? $event->estimated_cost,
                    $this->costSourceLabel($event->cost_source),
                    $status,
                    $error,
                    $event->duration_ms,
                    $event->attempt,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function costSourceLabel(string $source): string
    {
        return match ($source) {
            AiUsageEvent::COST_REPORTED => __('Reported'),
            AiUsageEvent::COST_ESTIMATED => __('Estimated'),
            default => __('Unknown'),
        };
    }
}
