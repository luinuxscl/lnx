<?php

namespace App\Http\Controllers;

use App\Models\SupportAttachment;
use App\Support\TicketService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportAttachmentController extends Controller
{
    public function __invoke(SupportAttachment $attachment, TicketService $service): StreamedResponse
    {
        $service->authorizeAttachment(auth()->user(), $attachment);

        return Storage::disk($attachment->disk)->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
