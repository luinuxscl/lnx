<?php

namespace App\Policies;

use App\Models\DocumentExtraction;
use App\Models\User;

class DocumentExtractionPolicy
{
    /**
     * Determine whether the user can view any document extractions.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the document extraction.
     */
    public function view(User $user, DocumentExtraction $document): bool
    {
        return (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can create document extractions.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the document extraction.
     */
    public function update(User $user, DocumentExtraction $document): bool
    {
        return (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can delete the document extraction.
     */
    public function delete(User $user, DocumentExtraction $document): bool
    {
        return (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can confirm the document extraction.
     */
    public function confirm(User $user, DocumentExtraction $document): bool
    {
        return (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can re-process the document extraction.
     */
    public function reprocess(User $user, DocumentExtraction $document): bool
    {
        return (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, DocumentExtraction $document): bool
    {
        return (int) $user->id === (int) $document->user_id;
    }
}
