<?php

use App\Enums\Role;
use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\AiUsageExportController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\HomeController;
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
use App\Livewire\Emails\Index as EmailsIndex;
use App\Livewire\Help\Admin\Index as HelpAdminIndex;
use App\Livewire\Help\Index as HelpIndex;
use App\Livewire\Knowledge\Bases as KnowledgeBases;
use App\Livewire\Knowledge\Index;
use App\Livewire\Knowledge\Show;
use App\Livewire\Navigation\Index as NavigationIndex;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Packages\Index as PackagesIndex;
use App\Livewire\Packages\LnxUi;
use App\Livewire\Setup\Index as SetupIndex;
use App\Livewire\Support\Index as SupportIndex;
use App\Livewire\Support\Show as SupportShow;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('invitations/{invitation}/{token}', [AcceptInvitationController::class, 'create'])->name('invitations.accept');
Route::post('invitations/{invitation}/{token}', [AcceptInvitationController::class, 'store'])
    ->middleware('throttle:10,1')->name('invitations.accept.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard')->menu([
        'label' => 'Dashboard',
        'icon' => 'squares-2x2',
        'audiences' => ['root', 'admin', 'user'],
    ]);
    Route::livewire('help', HelpIndex::class)->name('help.index')->menu([
        'label' => 'Help center',
        'icon' => 'lifebuoy',
        'audiences' => ['root', 'admin', 'user'],
        'region' => 'bottom',
    ]);
    Route::livewire('notifications', NotificationsIndex::class)->name('notifications.index');
    Route::livewire('chat/{conversation?}', AiChat::class)->name('chat')->menu([
        'label' => 'Chat',
        'icon' => 'chat-bubble-left-right',
        'group' => 'AI Tools',
        'group_icon' => 'sparkles',
        'audiences' => ['root'],
        'region' => 'bottom',
    ]);
    Route::livewire('knowledge', Index::class)->name('knowledge.index')->menu([
        'label' => 'RAG',
        'icon' => 'book-open',
        'group' => 'AI Tools',
        'group_icon' => 'sparkles',
        'audiences' => ['root'],
        'region' => 'bottom',
    ]);
    Route::livewire('knowledge/bases', KnowledgeBases::class)->name('knowledge.bases');
    Route::livewire('knowledge/{document}', Show::class)->name('knowledge.show');
    Route::livewire('documents', DocumentsIndex::class)->name('documents.index')->menu([
        'label' => 'Document AI',
        'icon' => 'document-text',
        'group' => 'AI Tools',
        'group_icon' => 'sparkles',
        'audiences' => ['root'],
        'region' => 'bottom',
    ]);
    Route::livewire('documents/{document}', DocumentsShow::class)->name('documents.show');
    Route::get('documents/{document}/download', [DocumentDownloadController::class, 'download'])->name('documents.download');
});

Route::middleware('auth')->group(function () {
    Route::livewire('support', SupportIndex::class)->name('support.index')->menu([
        'label' => 'Support',
        'icon' => 'inbox',
        'group' => 'Administration',
        'group_icon' => 'cog-6-tooth',
        'audiences' => ['root'],
        'roles' => ['root'],
    ]);
    Route::livewire('support/{ticket}', SupportShow::class)->name('support.show');
    Route::get('support/attachments/{attachment}', SupportAttachmentController::class)->name('support.attachments.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/users.php';

Route::livewire('admin/activity', ActivityIndex::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.activity.index')
    ->menu([
        'label' => 'Activity',
        'icon' => 'clock',
        'group' => 'Administration',
        'group_icon' => 'cog-6-tooth',
        'audiences' => ['root'],
        'roles' => ['root'],
    ]);

Route::livewire('admin/setup', SetupIndex::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.setup.index')
    ->menu([
        'label' => 'Checklist',
        'icon' => 'clipboard-document-check',
        'group' => 'Administration',
        'group_icon' => 'cog-6-tooth',
        'audiences' => ['root'],
        'roles' => ['root'],
    ]);

Route::livewire('admin/ai', AiStatus::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.ai.index')
    ->menu([
        'label' => 'AI',
        'icon' => 'sparkles',
        'group' => 'Administration',
        'group_icon' => 'cog-6-tooth',
        'audiences' => ['root'],
        'roles' => ['root'],
    ]);

Route::livewire('admin/packages', PackagesIndex::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.packages.index')
    ->menu([
        'label' => 'Installed packages',
        'icon' => 'cube',
        'group' => 'Packages',
        'group_icon' => 'cube',
        'audiences' => ['root'],
        'roles' => ['root'],
    ]);

Route::livewire('admin/packages/lnx-ui', LnxUi::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.packages.lnx-ui')
    ->menu([
        'label' => 'UI/UX components',
        'icon' => 'squares-2x2',
        'group' => 'Packages',
        'group_icon' => 'cube',
        'audiences' => ['root'],
        'roles' => ['root'],
    ]);

Route::livewire('admin/ai/usage', AiUsage::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.ai.usage');

Route::get('admin/ai/usage/export', AiUsageExportController::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.ai.usage.export');

Route::livewire('admin/emails', EmailsIndex::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.emails.index');

Route::livewire('admin/navigation', NavigationIndex::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.navigation.index')
    ->menu([
        'label' => 'Navigation',
        'icon' => 'bars-3',
        'group' => 'Administration',
        'group_icon' => 'cog-6-tooth',
        'audiences' => ['root'],
        'roles' => ['root'],
    ]);

Route::livewire('admin/help', HelpAdminIndex::class)
    ->middleware(['auth', 'role:'.Role::Root->value])
    ->name('admin.help.index')
    ->menu([
        'label' => 'Help center',
        'icon' => 'book-open',
        'group' => 'Administration',
        'group_icon' => 'cog-6-tooth',
        'audiences' => ['root'],
        'roles' => ['root'],
    ]);

if (config('lnx_ai.text_assistant.demo_enabled')) {
    Route::livewire('admin/ai/text-assistant', AiTextAssistantDemo::class)
        ->middleware(['auth', 'role:'.Role::Root->value])
        ->name('admin.ai.text-assistant');
}
