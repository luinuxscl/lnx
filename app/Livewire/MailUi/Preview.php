<?php

namespace App\Livewire\MailUi;

use Flux\Flux;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Demo shell that composes the shared mail pieces (x-lnx-ui::mail-list-item,
 * attachment-card, presence-list, mail-viewer) as an app Livewire component.
 * It holds only ephemeral preview state: there is no mail backend behind it.
 */
class Preview extends Component
{
    public string $activeFolder = 'inbox';

    public string $search = '';

    public ?int $selectedId = 2;

    /** @var array<int, int> */
    public array $starred = [1];

    /** @var array<int, int> */
    public array $checked = [];

    public string $reply = '';

    public function selectFolder(string $folder): void
    {
        $this->activeFolder = $folder;
    }

    public function selectMessage(int $id): void
    {
        $this->selectedId = $id;
    }

    public function toggleStar(int $id): void
    {
        $this->starred = in_array($id, $this->starred, true)
            ? array_values(array_diff($this->starred, [$id]))
            : [...$this->starred, $id];
    }

    public function toggleCheck(int $id): void
    {
        $this->checked = in_array($id, $this->checked, true)
            ? array_values(array_diff($this->checked, [$id]))
            : [...$this->checked, $id];
    }

    public function toggleAll(): void
    {
        $ids = array_column($this->demoMessages(), 'id');

        $this->checked = count($this->checked) === count($ids) ? [] : $ids;
    }

    public function sendReply(): void
    {
        $this->validate(
            ['reply' => ['required', 'string', 'min:2']],
            [],
            ['reply' => __('Reply')],
        );

        $this->reply = '';
        Flux::toast(variant: 'success', text: __('Reply sent (demo).'));
    }

    public function isStarred(int $id): bool
    {
        return in_array($id, $this->starred, true);
    }

    public function isChecked(int $id): bool
    {
        return in_array($id, $this->checked, true);
    }

    public function render(): View
    {
        return view('livewire.mail-ui.preview', [
            'folders' => $this->folders(),
            'labels' => $this->labels(),
            'messages' => $this->visibleMessages(),
            'people' => $this->people(),
            'selected' => $this->selected(),
        ]);
    }

    /**
     * @return array<int, array{key: string, label: string, icon: string, badge: ?int}>
     */
    private function folders(): array
    {
        return [
            ['key' => 'inbox', 'label' => __('Inbox'), 'icon' => 'inbox', 'badge' => 8],
            ['key' => 'sent', 'label' => __('Sent'), 'icon' => 'paper-airplane', 'badge' => null],
            ['key' => 'drafts', 'label' => __('Drafts'), 'icon' => 'document', 'badge' => null],
            ['key' => 'spam', 'label' => __('Spam'), 'icon' => 'exclamation-circle', 'badge' => 4],
            ['key' => 'important', 'label' => __('Important'), 'icon' => 'bookmark', 'badge' => null],
            ['key' => 'trash', 'label' => __('Trash'), 'icon' => 'trash', 'badge' => null],
            ['key' => 'archive', 'label' => __('Archive'), 'icon' => 'archive-box', 'badge' => null],
            ['key' => 'starred', 'label' => __('Starred'), 'icon' => 'star', 'badge' => 12],
        ];
    }

    /**
     * @return array<int, array{label: string, color: string}>
     */
    private function labels(): array
    {
        return [
            ['label' => __('Mail'), 'color' => 'accent'],
            ['label' => __('Home'), 'color' => 'rose'],
            ['label' => __('Work'), 'color' => 'emerald'],
            ['label' => __('Friends'), 'color' => 'blue'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function demoMessages(): array
    {
        return [
            [
                'id' => 1,
                'sender' => 'Sujika',
                'email' => 'sujika@example.com',
                'subject' => 'New Project details',
                'preview' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit',
                'time' => '1:32PM',
                'badge' => null,
                'unread' => true,
                'attachments' => [],
            ],
            [
                'id' => 2,
                'sender' => 'Michael Jeremy',
                'email' => 'michaeljeremy2194@gmail.com',
                'subject' => 'History of planets are discovered yesterday',
                'preview' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit',
                'time' => '10:27AM',
                'badge' => null,
                'unread' => true,
                'attachments' => [
                    ['name' => 'Earth_Archeology_Report.pdf', 'size' => '0.85MB', 'icon' => 'document-text', 'color' => 'blue'],
                    ['name' => 'Planets_Image_Archive.zip', 'size' => '457KB', 'icon' => 'photo', 'color' => 'violet'],
                ],
                'body' => [
                    'Hi, J\'son Taylor Greetings 👋',
                    'Earth, our home, is the third planet from the sun. While scientists continue to hunt for clues of life beyond Earth, our home planet remains the only place in the universe where we\'ve ever identified living organisms.',
                    'Earth has a diameter of roughly 8,000 miles (13,000 kilometers) and is mostly round because gravity generally pulls matter into a ball. But the spin of our home planet causes it to be squashed at its poles and swollen at the equator, making the true shape of the Earth an "oblate spheroid."',
                ],
                'date' => 'Oct-22-2022, 03:05PM',
            ],
            [
                'id' => 3,
                'sender' => 'King Berunda',
                'email' => 'king@example.com',
                'subject' => 'Request for return of Item?',
                'preview' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit',
                'time' => 'Yesterday, 10:27AM',
                'badge' => ['label' => __('Promotion'), 'color' => 'emerald'],
                'unread' => false,
                'attachments' => [],
            ],
            [
                'id' => 4,
                'sender' => 'Samantha May',
                'email' => 'samantha@example.com',
                'subject' => 'Completion of work is pending',
                'preview' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit',
                'time' => '24 Oct, 5:45PM',
                'badge' => ['label' => __('Social'), 'color' => 'violet'],
                'unread' => true,
                'attachments' => [],
            ],
            [
                'id' => 5,
                'sender' => 'Dawn Bradshaw',
                'email' => 'dawn@example.com',
                'subject' => 'Personal Mail',
                'preview' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit',
                'time' => '11 Sep, 10:32AM',
                'badge' => ['label' => __('Personal'), 'color' => 'rose'],
                'unread' => false,
                'attachments' => [],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function visibleMessages(): array
    {
        $messages = $this->demoMessages();

        if ($this->activeFolder === 'starred') {
            $messages = array_values(array_filter(
                $messages,
                fn (array $message): bool => $this->isStarred($message['id']),
            ));
        }

        if (trim($this->search) !== '') {
            $needle = mb_strtolower(trim($this->search));
            $messages = array_values(array_filter(
                $messages,
                fn (array $message): bool => str_contains(mb_strtolower($message['sender'].' '.$message['subject']), $needle),
            ));
        }

        return $messages;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function selected(): ?array
    {
        foreach ($this->demoMessages() as $message) {
            if ($message['id'] === $this->selectedId) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{name: string, line: ?string, online: bool}>
     */
    private function people(): array
    {
        return [
            ['name' => 'Angelica', 'line' => 'Hello this is angelica.', 'online' => true],
            ['name' => 'Rexha', 'line' => 'Thanks for sharing file 😊', 'online' => true],
            ['name' => 'Amelia Chen', 'line' => 'Design', 'online' => true],
            ['name' => 'Noah Patel', 'line' => 'Operations', 'online' => false],
            ['name' => 'Sofia Rossi', 'line' => 'Support', 'online' => true],
        ];
    }
}
