<?php

namespace App\Support\Dashboard\Widgets;

use App\Enums\Role;
use App\Models\User;
use App\Support\Dashboard\ActionData;
use App\Support\Dashboard\DashboardSection;
use App\Support\Dashboard\Widget;
use App\Support\Dashboard\WidgetData;
use App\Support\Dashboard\WidgetItem;
use App\Support\Dashboard\WidgetType;

/**
 * Root's access panel: administration tools and the app tools in one place.
 */
class RootToolsWidget extends Widget
{
    public function id(): string
    {
        return 'lnx.root_tools';
    }

    public function section(): DashboardSection
    {
        return DashboardSection::Access;
    }

    public function type(): WidgetType
    {
        return WidgetType::Action;
    }

    public function title(): ?string
    {
        return __('Root tools');
    }

    public function order(): int
    {
        return 20;
    }

    public function span(): int
    {
        return 12;
    }

    public function visible(User $actor): bool
    {
        return $actor->hasRole(Role::Root);
    }

    public function resolve(User $actor): ?WidgetData
    {
        if (! $this->visible($actor)) {
            return null;
        }

        return new ActionData([
            new WidgetItem(
                __('Support'),
                __('Review and answer support requests from every user.'),
                href: route('support.index'),
                icon: 'inbox',
                color: 'rose',
            ),
            new WidgetItem(
                __('Users'),
                __('Manage accounts, roles, and invitations.'),
                href: route('admin.users.index'),
                icon: 'users',
                color: 'blue',
            ),
            new WidgetItem(
                __('Activity'),
                __('Audit administrative changes across the application.'),
                href: route('admin.activity.index'),
                icon: 'clock',
                color: 'violet',
            ),
            new WidgetItem(
                __('Settings'),
                __('Configure site identity, branding, and options.'),
                href: route('admin.settings.edit'),
                icon: 'cog',
                color: 'cyan',
            ),
            new WidgetItem(
                __('Checklist'),
                __('Verify that the installation is fully set up.'),
                href: route('admin.setup.index'),
                icon: 'clipboard-document-check',
                color: 'amber',
            ),
            new WidgetItem(
                __('AI'),
                __('Check the AI provider status and configuration.'),
                href: route('admin.ai.index'),
                icon: 'sparkles',
                color: 'emerald',
            ),
            new WidgetItem(
                __('RAG'),
                __('Query your own knowledge base with cited answers.'),
                href: route('knowledge.index'),
                icon: 'book-open',
            ),
            new WidgetItem(
                __('Document AI'),
                __('Extract structured data from documents.'),
                href: route('documents.index'),
                icon: 'document-text',
            ),
        ]);
    }
}
