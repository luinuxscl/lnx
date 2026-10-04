<?php

use App\Ai\Agents\DocumentExtractionAgent;
use App\Ai\AiErrorCategorizer;
use App\Ai\AiGateway;
use App\Ai\DocumentExtractor\ExtractionValidator;
use App\Ai\DocumentExtractor\TextExtractor;
use App\Events\DocumentExtractionConfirmed;
use App\Jobs\ProcessDocumentExtractionJob;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Documents\Show as DocumentsShow;
use App\Models\DocumentExtraction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
    Queue::fake();

    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', [
        'test/model' => [
            'tools' => false,
            'structured' => true,
            'streaming' => false,
            'context' => 128000,
        ],
    ]);
    config()->set('ai.providers.openrouter.key', 'test-key');
});

it('forbids guests from viewing document extraction routes', function () {
    $this->get(route('documents.index'))->assertRedirect(route('login'));
});

it('allows authenticated users to access index page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertOk()
        ->assertSee('Extracción de documentos');
});

it('allows user to upload document and queues processing job', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('invoice.txt', 50, 'text/plain');

    Livewire::actingAs($user)
        ->test(DocumentsIndex::class)
        ->set('file', $file)
        ->call('upload')
        ->assertHasNoErrors();

    $document = DocumentExtraction::where('user_id', $user->id)->first();
    expect($document)->not->toBeNull()
        ->and($document->original_filename)->toBe('invoice.txt')
        ->and($document->status)->toBe(DocumentExtraction::STATUS_PENDING);

    Queue::assertPushed(ProcessDocumentExtractionJob::class, function ($job) use ($document) {
        return $job->document->id === $document->id;
    });
});

it('prevents user B from accessing user A document', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $document = DocumentExtraction::factory()->create([
        'user_id' => $userA->id,
    ]);

    $this->actingAs($userB)
        ->get(route('documents.show', $document))
        ->assertForbidden();

    $this->actingAs($userB)
        ->get(route('documents.download', $document))
        ->assertForbidden();

    Livewire::actingAs($userB)
        ->test(DocumentsShow::class, ['document' => $document])
        ->assertForbidden();
});

it('processes document extraction job and updates status to review_required', function () {
    $user = User::factory()->create();
    $filePath = 'documents/'.$user->id.'/test_invoice.txt';
    Storage::disk('local')->put($filePath, 'Invoice INV-999 Date: 2026-10-04 Total: $250.00 Vendor: Acme Corp');

    $document = DocumentExtraction::factory()->create([
        'user_id' => $user->id,
        'file_path' => $filePath,
        'original_filename' => 'test_invoice.txt',
        'status' => DocumentExtraction::STATUS_PENDING,
    ]);

    $jsonResponse = json_encode([
        'document_type' => 'invoice',
        'document_number' => 'INV-999',
        'date' => '2026-10-04',
        'issuer_name' => 'Acme Corp',
        'recipient_name' => 'Client Inc',
        'total_amount' => 250.00,
        'currency' => 'USD',
        'line_items' => [
            ['description' => 'Development Services', 'quantity' => 1, 'price' => 250.00],
        ],
        'summary' => 'Invoice for development services',
    ]);

    DocumentExtractionAgent::fake([
        $jsonResponse,
    ]);

    $job = new ProcessDocumentExtractionJob($document);
    $job->handle(
        app(TextExtractor::class),
        app(AiGateway::class),
        app(ExtractionValidator::class),
        app(AiErrorCategorizer::class),
    );

    $document->refresh();

    expect($document->status)->toBe(DocumentExtraction::STATUS_REVIEW_REQUIRED)
        ->and($document->extracted_data['document_number'])->toBe('INV-999')
        ->and($document->extracted_data['total_amount'])->toEqual(250.0)
        ->and($document->reviewed_data['issuer_name'])->toBe('Acme Corp');
});

it('allows user to review, edit draft, and confirm document', function () {
    Event::fake();

    $user = User::factory()->create();
    $document = DocumentExtraction::factory()->reviewRequired()->create([
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(DocumentsShow::class, ['document' => $document])
        ->set('document_number', 'INV-UPDATED-100')
        ->set('total_amount', 300.00)
        ->call('saveDraft');

    $document->refresh();
    expect($document->reviewed_data['document_number'])->toBe('INV-UPDATED-100')
        ->and($document->status)->toBe(DocumentExtraction::STATUS_REVIEW_REQUIRED);

    Livewire::actingAs($user)
        ->test(DocumentsShow::class, ['document' => $document])
        ->call('confirm');

    $document->refresh();
    expect($document->status)->toBe(DocumentExtraction::STATUS_CONFIRMED)
        ->and($document->confirmed_at)->not->toBeNull();

    Event::assertDispatched(DocumentExtractionConfirmed::class, function ($event) use ($document) {
        return $event->document->id === $document->id;
    });
});

it('allows authorized owner to download private file', function () {
    $user = User::factory()->create();
    $filePath = 'documents/'.$user->id.'/sample.txt';
    Storage::disk('local')->put($filePath, 'Private content');

    $document = DocumentExtraction::factory()->create([
        'user_id' => $user->id,
        'file_path' => $filePath,
        'original_filename' => 'sample.txt',
    ]);

    $response = $this->actingAs($user)
        ->get(route('documents.download', $document));

    $response->assertOk();
});
