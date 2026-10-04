<?php

namespace App\Support;

use App\Enums\Role;
use App\Events\SupportPublicReplyAdded;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketStatusChanged;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketUpdated;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public const TYPES = ['problem', 'idea', 'question'];

    public const STATUSES = ['received', 'in_progress', 'waiting_for_requester', 'resolved', 'closed'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data, ?UploadedFile $image, string $requestKey): SupportTicket
    {
        if ($existing = SupportTicket::where('request_key', $requestKey)->first()) {
            abort_unless($existing->user_id === $actor->id, 403);

            return $existing;
        }

        $ticket = DB::transaction(function () use ($actor, $data, $image, $requestKey): SupportTicket {
            $ticket = SupportTicket::create([
                'user_id' => $actor->id,
                'type' => $data['type'],
                'status' => 'received',
                'title' => $data['title'],
                'description' => $data['description'],
                'blocking' => $data['blocking'] ?? null,
                'screen' => $data['screen'] ?? null,
                'route_name' => $data['route_name'] ?? null,
                'app_version' => $data['app_version'] ?? config('app.version'),
                'window_width' => $data['window_width'] ?? null,
                'window_height' => $data['window_height'] ?? null,
                'locale' => $data['locale'] ?? null,
                'theme' => $data['theme'] ?? null,
                'opened_at' => $data['opened_at'] ?? now(),
                'request_key' => $requestKey,
            ]);
            if ($image) {
                $this->attach($ticket, null, $image);
            }

            return $ticket;
        });

        SupportTicketCreated::dispatch($ticket->id);
        $this->notifyRoots($ticket, $actor, 'created');

        return $ticket;
    }

    public function reply(User $actor, SupportTicket $ticket, string $body, bool $internal, ?UploadedFile $image, string $requestKey): SupportMessage
    {
        $this->authorizeView($actor, $ticket);
        abort_if($internal && ! $actor->hasRole(Role::Root), 403);
        abort_if(! $actor->hasRole(Role::Root) && $ticket->status === 'closed', 403);

        if ($existing = SupportMessage::where('request_key', $requestKey)->first()) {
            abort_unless($existing->support_ticket_id === $ticket->id && $existing->user_id === $actor->id, 403);

            return $existing;
        }

        $message = DB::transaction(function () use ($actor, $ticket, $body, $internal, $image, $requestKey): SupportMessage {
            $message = $ticket->messages()->create([
                'user_id' => $actor->id,
                'body' => $body,
                'internal' => $internal,
                'request_key' => $requestKey,
            ]);
            if ($image) {
                $this->attach($ticket, $message, $image);
            }

            return $message;
        });

        if (! $internal) {
            SupportPublicReplyAdded::dispatch($ticket->id, $message->id);
            if ($actor->hasRole(Role::Root)) {
                $this->notifyRequester($ticket, $actor, 'reply');
            } else {
                $this->notifyRoots($ticket, $actor, 'reply');
            }
        }

        return $message;
    }

    public function changeStatus(User $actor, SupportTicket $ticket, string $status, ?string $resolution, string $requestKey): void
    {
        $this->authorizeView($actor, $ticket);
        abort_unless(in_array($status, self::STATUSES, true), 422);
        $usedKey = DB::table('support_status_changes')->where('request_key', $requestKey)->first();
        if ($usedKey !== null) {
            abort_unless($usedKey->support_ticket_id === $ticket->id && $usedKey->user_id === $actor->id, 403);

            return;
        }

        $ticket->refresh();
        $root = $actor->hasRole(Role::Root);
        $allowed = $root ? match ($ticket->status) {
            'received' => ['in_progress', 'waiting_for_requester', 'resolved', 'closed'],
            'in_progress' => ['waiting_for_requester', 'resolved', 'closed'],
            'waiting_for_requester' => ['in_progress', 'resolved', 'closed'],
            'resolved' => ['in_progress', 'closed'],
            'closed' => [],
            default => [],
        } : ($ticket->status === 'resolved' ? ['received'] : []);

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => __('This status transition is not allowed.')]);
        }
        if (in_array($status, ['resolved', 'closed'], true) && blank($resolution)) {
            throw ValidationException::withMessages(['resolution' => __('Explain the resolution or closure.')]);
        }
        $previous = $ticket->status;
        DB::transaction(function () use ($actor, $ticket, $status, $resolution, $requestKey, $previous): void {
            $ticket->update(['status' => $status, 'resolution' => $resolution]);
            DB::table('support_status_changes')->insert([
                'support_ticket_id' => $ticket->id,
                'user_id' => $actor->id,
                'previous_status' => $previous,
                'current_status' => $status,
                'resolution' => $resolution,
                'request_key' => $requestKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
        SupportTicketStatusChanged::dispatch($ticket->id, $previous, $status);
        if ($root) {
            $this->notifyRequester($ticket, $actor, 'status');
        } else {
            $this->notifyRoots($ticket, $actor, 'reopened');
        }
    }

    public function setPriority(User $actor, SupportTicket $ticket, string $priority): void
    {
        abort_unless($actor->hasRole(Role::Root), 403);
        abort_unless(in_array($priority, self::PRIORITIES, true), 422);
        $ticket->update(['priority' => $priority]);
    }

    public function authorizeView(User $actor, SupportTicket $ticket): void
    {
        if (! $actor->hasRole(Role::Root) && $ticket->user_id !== $actor->id) {
            throw (new ModelNotFoundException)->setModel(SupportTicket::class, [$ticket->id]);
        }
    }

    public function authorizeAttachment(User $actor, SupportAttachment $attachment): void
    {
        $this->authorizeView($actor, $attachment->ticket);
        if ($attachment->message?->internal && ! $actor->hasRole(Role::Root)) {
            throw (new ModelNotFoundException)->setModel(SupportAttachment::class, [$attachment->id]);
        }
    }

    private function attach(SupportTicket $ticket, ?SupportMessage $message, UploadedFile $image): void
    {
        $path = $image->store('support/'.$ticket->id, 'local');
        $ticket->attachments()->create([
            'support_message_id' => $message?->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $image->getClientOriginalName(),
            'mime_type' => $image->getMimeType(),
            'size' => $image->getSize(),
        ]);
    }

    private function notifyRoots(SupportTicket $ticket, User $actor, string $action): void
    {
        foreach (User::role(Role::Root->value)->whereKeyNot($actor->id)->get() as $root) {
            $root->notify(new SupportTicketUpdated($ticket->id, $action));
        }
    }

    private function notifyRequester(SupportTicket $ticket, User $actor, string $action): void
    {
        if ($ticket->user_id !== $actor->id) {
            $ticket->user->notify(new SupportTicketUpdated($ticket->id, $action));
        }
    }
}
