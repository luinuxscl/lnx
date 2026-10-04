<?php

namespace App\Http\Controllers;

use App\Models\DocumentExtraction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    use AuthorizesRequests;

    /**
     * Download the original private document file.
     */
    public function download(DocumentExtraction $document): StreamedResponse
    {
        $this->authorize('download', $document);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, __('Document file not found.'));
        }

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }
}
