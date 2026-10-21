<?php

namespace App\Support\Help;

use App\Enums\KnowledgeAudience;
use App\Jobs\IndexKnowledgeDocumentJob;
use App\Models\HelpEntry;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use Illuminate\Database\Eloquent\Collection;

/**
 * Mirrors the published help catalog into the dedicated knowledge base.
 *
 * The help center owns its content; Knowledge only stores the searchable
 * chunks. One document per entry and locale keeps the language filter exact,
 * and the system base stays out of the regular Knowledge UI so it can never be
 * attached to a chat without the role filter that HelpSearchService applies.
 */
class HelpKnowledgeSync
{
    public const BASE_SLUG = 'product_help';

    /**
     * Locales that are indexed for every published entry.
     *
     * @var list<string>
     */
    public const LOCALES = ['es', 'en'];

    /**
     * Synchronize every published help entry across all supported locales.
     */
    public function syncAll(): void
    {
        $base = $this->ensureBase();
        $expected = [];

        foreach ($this->publishedEntries() as $entry) {
            foreach (self::LOCALES as $locale) {
                $this->syncDocument($base, $entry, $locale);
                $expected[] = $this->documentKey($entry->slug, $locale);
            }
        }

        $this->removeStaleDocuments($expected);
    }

    /**
     * Synchronize (or remove) a single entry without touching the rest.
     */
    public function syncEntry(string $slug): void
    {
        $base = $this->ensureBase();
        $entry = HelpEntry::query()->with('group')->where('slug', $slug)->first();

        if ($entry === null || ! $this->isPublished($entry)) {
            KnowledgeDocument::query()
                ->where('source_type', 'help')
                ->where('help_slug', $slug)
                ->delete();

            return;
        }

        $expected = [];

        foreach (self::LOCALES as $locale) {
            $this->syncDocument($base, $entry, $locale);
            $expected[] = $this->documentKey($slug, $locale);
        }

        KnowledgeDocument::query()
            ->where('source_type', 'help')
            ->where('help_slug', $slug)
            ->whereNotIn('locale', self::LOCALES)
            ->delete();

        $this->removeStaleDocuments($expected, $slug);
    }

    private function ensureBase(): KnowledgeBase
    {
        $base = KnowledgeBase::query()->firstOrCreate(
            ['slug' => self::BASE_SLUG],
            [
                'name' => 'Product help',
                'description' => 'Internal knowledge base for the help center.',
                'audience' => KnowledgeAudience::Root->value,
                'is_active' => true,
                'is_system' => true,
                'created_by' => null,
            ],
        );

        if (! $base->is_system || $base->audience !== KnowledgeAudience::Root || ! $base->is_active) {
            $base->update([
                'audience' => KnowledgeAudience::Root->value,
                'is_active' => true,
                'is_system' => true,
            ]);
        }

        return $base;
    }

    /**
     * @return Collection<int, HelpEntry>
     */
    private function publishedEntries(): Collection
    {
        return HelpEntry::query()
            ->with('group')
            ->where('enabled', true)
            ->whereHas('group', fn ($query) => $query->where('enabled', true))
            ->orderBy('id')
            ->get();
    }

    private function isPublished(HelpEntry $entry): bool
    {
        return $entry->enabled && $entry->group?->enabled === true;
    }

    private function syncDocument(KnowledgeBase $base, HelpEntry $entry, string $locale): void
    {
        $title = trim($entry->resolvedContent('title', $locale));
        $summary = trim($entry->resolvedContent('summary', $locale));
        $body = trim($entry->resolvedContent('body', $locale));

        $content = collect([
            '# '.$title,
            $summary,
            $body,
        ])->filter(fn (string $part): bool => trim($part) !== '')->implode("\n\n");

        $document = KnowledgeDocument::query()->firstOrNew([
            'help_slug' => $entry->slug,
            'locale' => $locale,
        ]);

        $changed = ! $document->exists
            || $document->title !== $title
            || $document->content !== $content
            || (int) $document->knowledge_base_id !== (int) $base->id
            || $document->source_type !== 'help'
            || $document->withdrawn_at !== null;

        $document->fill([
            'user_id' => null,
            'knowledge_base_id' => $base->id,
            'title' => $title,
            'source_type' => 'help',
            'help_slug' => $entry->slug,
            'locale' => $locale,
            'content' => $content,
            'withdrawn_at' => null,
        ]);

        if (! $changed && $document->status === 'indexed') {
            return;
        }

        $document->fill([
            'status' => 'indexing',
            'error_message' => null,
        ])->save();

        IndexKnowledgeDocumentJob::dispatch($document);
    }

    /**
     * @param  list<string>  $expectedKeys
     */
    private function removeStaleDocuments(array $expectedKeys, ?string $slug = null): void
    {
        KnowledgeDocument::query()
            ->where('source_type', 'help')
            ->when($slug !== null, fn ($query) => $query->where('help_slug', $slug))
            ->get()
            ->each(function (KnowledgeDocument $document) use ($expectedKeys): void {
                if (! in_array($this->documentKey((string) $document->help_slug, (string) $document->locale), $expectedKeys, true)) {
                    $document->delete();
                }
            });
    }

    private function documentKey(string $slug, string $locale): string
    {
        return $slug.'|'.$locale;
    }
}
