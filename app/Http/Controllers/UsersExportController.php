<?php

namespace App\Http\Controllers;

use App\Actions\SearchUsers;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersExportController extends Controller
{
    use AuthorizesRequests;

    /**
     * Stream the filtered user list as a CSV download.
     */
    public function __invoke(): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $status = (string) request()->query('status', 'all');
        $role = (string) request()->query('role', 'all');
        $verification = (string) request()->query('verified', 'all');
        $sortBy = (string) request()->query('sort', 'name');
        $sortDirection = (string) request()->query('dir', 'asc');

        $users = app(SearchUsers::class)->handle(
            search: (string) request()->query('q', ''),
            status: in_array($status, SearchUsers::STATUSES, true) ? $status : 'all',
            role: in_array($role, SearchUsers::ROLES, true) ? $role : 'all',
            perPage: 1000,
            verification: in_array($verification, SearchUsers::VERIFICATIONS, true) ? $verification : 'all',
            sortBy: array_key_exists($sortBy, SearchUsers::SORTS) ? $sortBy : 'name',
            sortDirection: $sortDirection === 'desc' ? 'desc' : 'asc',
        );

        $filename = 'users-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($users): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [__('Name'), __('Email'), __('Role'), __('Status'), __('Email verification'), __('Registered')]);

            foreach ($users->items() as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $this->roleLabel($user),
                    $user->isSuspended() ? __('Suspended') : __('Active'),
                    $user->email_verified_at !== null ? __('Verified') : __('Unverified'),
                    $user->created_at?->format('d/m/Y'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function roleLabel(User $user): string
    {
        if ($user->hasRole(Role::Root)) {
            return __(Role::Root->label());
        }

        if ($user->hasRole(Role::Admin)) {
            return __(Role::Admin->label());
        }

        return __('Standard user');
    }
}
