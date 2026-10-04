<?php

use App\Enums\Role;
use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SupportAttachmentController;
use App\Livewire\Activity\Index as ActivityIndex;
use App\Livewire\Ai\Chat as AiChat;
use App\Livewire\Ai\Status as AiStatus;
use App\Livewire\Ai\TextAssistantDemo as AiTextAssistantDemo;
use App\Livewire\Ai\Usage as AiUsage;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Documents\Show as DocumentsShow;
use App\Livewire\Knowledge\Index;
use App\Livewire\Knowledge\Show;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Setup\Index as SetupIndex;
use App\Livewire\Support\Index as SupportIndex;
use App\Livewire\Support\Show as SupportShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('invitations/{invitation}/{token}', [AcceptInvitationController::class, 'create'])->name('invitations.accept');
Route::post('invitations/{invitation}/{token}', [AcceptInvitationController::class, 'store'])
    ->middleware('throttle:10,1')->name('invitations.accept.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('notifications', NotificationsIndex::class)->name('notifications.index');
    Route::livewire('chat/{conversation?}', AiChat::class)->name('chat');
    Route::livewire('knowledge', Index::class)->name('knowledge.index');
    Route::livewire('knowledge/{document}', Show::class)->name('knowledge.show');
    Route::livewire('documents', DocumentsIndex::class)->name('documents.index');
    Route::livewire('documents/{document}', DocumentsShow::class)->name('documents.show');
    Route::get('documents/{document}/download', [DocumentDownloadController::class, 'download'])->name('documents.download');

    if (app()->environment(['local', 'testing']) && config('app.design_gallery_enabled')) {
        Route::view('design-gallery', 'design-gallery')
            ->middleware('role:'.Role::Root->value)
            ->name('design-gallery');
    }
});

Route::middleware('auth')->group(function () {
    Route::livewire('support', SupportIndex::class)->name('support.index');
    Route::livewire('support/{ticket}', SupportShow::class)->name('support.show');
    Route::get('support/attachments/{attachment}', SupportAttachmentController::class)->name('support.attachments.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/users.php';

Route::livewire('admin/activity', ActivityIndex::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.activity.index');

Route::livewire('admin/setup', SetupIndex::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.setup.index');

Route::livewire('admin/ai', AiStatus::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.ai.index');

Route::livewire('admin/ai/usage', AiUsage::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.ai.usage');

if (config('lnx_ai.text_assistant.demo_enabled')) {
    Route::livewire('admin/ai/text-assistant', AiTextAssistantDemo::class)
        ->middleware(['auth', 'role:'.Role::Root->value])
        ->name('admin.ai.text-assistant');
}
