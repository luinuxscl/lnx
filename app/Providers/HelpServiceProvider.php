<?php

namespace App\Providers;

use App\Enums\Role;
use App\Support\Help\HelpRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Declares lnx's own help content.
 *
 * The content lives in `lang/{locale}/help.php` as Markdown so it follows the
 * current locale. Packages follow the same registry contract from their own
 * service providers.
 */
class HelpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app(HelpRegistry::class)->register('lnx', [
            ...$this->guides(),
            ...$this->faqs(),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function guides(): array
    {
        return [
            'lnx.getting-started' => $this->guide('getting_started', 'getting_started', 1, route: 'dashboard'),
            'lnx.dashboard' => $this->guide('dashboard', 'getting_started', 2, route: 'dashboard'),
            'lnx.profile-security' => $this->guide('profile_security', 'account', 3, route: 'profile.edit'),
            'lnx.notifications' => $this->guide('notifications', 'account', 4, route: 'notifications.index'),
            'lnx.users-invitations' => $this->guide('users_invitations', 'collaboration', 5, [Role::Admin->value], 'admin.users.index'),
            'lnx.feedback-support' => $this->guide('feedback_support', 'support', 6, route: 'support.index'),
            'lnx.chat' => $this->guide('chat', 'ai', 7, [Role::Root->value], 'chat'),
            'lnx.knowledge' => $this->guide('knowledge', 'ai', 8, [Role::Root->value], 'knowledge.index'),
            'lnx.documents' => $this->guide('documents', 'ai', 9, [Role::Root->value], 'documents.index'),
            'lnx.emails' => $this->guide('emails', 'operations', 10, [Role::Root->value], 'admin.emails.index'),
            'lnx.packages' => $this->guide('packages', 'operations', 11, [Role::Root->value], 'admin.packages.index'),
            'lnx.activity' => $this->guide('activity', 'operations', 12, [Role::Root->value], 'admin.activity.index'),
            'lnx.setup' => $this->guide('setup', 'operations', 13, [Role::Root->value], 'admin.setup.index'),
            'lnx.preferences' => $this->guide('preferences', 'account', 14, route: 'appearance.edit'),
            'lnx.general-settings' => $this->guide('general_settings', 'operations', 15, [Role::Root->value], 'admin.settings.edit'),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function faqs(): array
    {
        return [
            'lnx.faq.reset-password' => $this->faq('reset_password', 'access', 101),
            'lnx.faq.change-email' => $this->faq('change_email', 'access', 102),
            'lnx.faq.passkeys' => $this->faq('passkeys', 'access', 103),
            'lnx.faq.two-factor' => $this->faq('two_factor', 'access', 104),
            'lnx.faq.language' => $this->faq('language', 'access', 105),
            'lnx.faq.notifications' => $this->faq('notifications', 'access', 106),
            'lnx.faq.contact-support' => $this->faq('contact_support', 'support', 107),
            'lnx.faq.request-status' => $this->faq('request_status', 'support', 108),
            'lnx.faq.ai-disabled' => $this->faq('ai_disabled', 'ai', 109, [Role::Root->value]),
            'lnx.faq.ai-cost' => $this->faq('ai_cost', 'ai', 110, [Role::Root->value]),
            'lnx.faq.install-package' => $this->faq('install_package', 'packages', 111, [Role::Root->value]),
            'lnx.faq.package-permissions' => $this->faq('package_permissions', 'packages', 112, [Role::Root->value]),
            'lnx.faq.privacy' => $this->faq('privacy', 'support', 113),
            'lnx.faq.invite-user' => $this->faq('invite_user', 'collaboration', 114, [Role::Admin->value]),
            'lnx.faq.suspend-user' => $this->faq('suspend_user', 'collaboration', 115, [Role::Admin->value]),
        ];
    }

    /**
     * @param  list<string>  $roles
     * @return array<string, mixed>
     */
    private function guide(string $key, string $group, int $order, array $roles = [], ?string $route = null): array
    {
        return [
            'type' => 'article',
            'group' => 'help.groups.'.$group,
            'title' => 'help.guides.'.$key.'.title',
            'summary' => 'help.guides.'.$key.'.summary',
            'body' => 'help.guides.'.$key.'.body',
            'roles' => $roles,
            'order' => $order,
            'route' => $route,
        ];
    }

    /**
     * @param  list<string>  $roles
     * @return array<string, mixed>
     */
    private function faq(string $key, string $group, int $order, array $roles = []): array
    {
        return [
            'type' => 'faq',
            'group' => 'help.groups.'.$group,
            'title' => 'help.faq.'.$key.'.title',
            'summary' => 'help.faq.'.$key.'.summary',
            'body' => 'help.faq.'.$key.'.body',
            'roles' => $roles,
            'order' => $order,
        ];
    }
}
