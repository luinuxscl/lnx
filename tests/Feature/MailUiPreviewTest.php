<?php

use App\Livewire\MailUi\Preview;
use Livewire\Livewire;

it('renders the mail preview shell with the shared pieces', function () {
    Livewire::test(Preview::class)
        ->assertSee(__('All mails'))
        ->assertSee(__('Online users'))
        ->assertSee('Michael Jeremy')
        ->assertSee('Earth_Archeology_Report.pdf');
});

it('selects a message for the detail pane', function () {
    Livewire::test(Preview::class)
        ->assertSet('selectedId', 2)
        ->call('selectMessage', 5)
        ->assertSet('selectedId', 5)
        ->assertSee('Personal Mail');
});

it('toggles the starred state of a message', function () {
    Livewire::test(Preview::class)
        ->assertSet('starred', [1])
        ->call('toggleStar', 3)
        ->assertSet('starred', [1, 3])
        ->call('toggleStar', 1)
        ->assertSet('starred', [3]);
});

it('filters messages by folder and search term', function () {
    Livewire::test(Preview::class)
        ->call('selectFolder', 'starred')
        ->assertSee('Sujika')
        ->assertDontSee('King Berunda')
        ->call('selectFolder', 'inbox')
        ->set('search', 'king')
        ->assertSee('King Berunda')
        ->assertDontSee('Sujika');
});

it('requires a reply before sending it', function () {
    Livewire::test(Preview::class)
        ->set('reply', '')
        ->call('sendReply')
        ->assertHasErrors(['reply' => 'required']);

    Livewire::test(Preview::class)
        ->set('reply', 'Thanks!')
        ->call('sendReply')
        ->assertHasNoErrors()
        ->assertSet('reply', '');
});
