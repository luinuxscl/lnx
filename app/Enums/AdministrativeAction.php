<?php

namespace App\Enums;

enum AdministrativeAction: string
{
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case UserRoleChanged = 'user_role_changed';
    case UserPasswordChanged = 'user_password_changed';
    case UserDeleted = 'user_deleted';
    case UserSuspended = 'user_suspended';
    case UserReactivated = 'user_reactivated';
    case SettingsUpdated = 'settings_updated';
    case InvitationCreated = 'invitation_created';
    case InvitationRenewed = 'invitation_renewed';
    case InvitationRevoked = 'invitation_revoked';
    case InvitationAccepted = 'invitation_accepted';
    case InvitationSent = 'invitation_sent';
    case InvitationSendFailed = 'invitation_send_failed';
    case NavigationGroupCreated = 'navigation_group_created';
    case NavigationGroupUpdated = 'navigation_group_updated';
    case NavigationGroupDeleted = 'navigation_group_deleted';
    case NavigationItemCreated = 'navigation_item_created';
    case NavigationItemUpdated = 'navigation_item_updated';
    case NavigationItemDeleted = 'navigation_item_deleted';
    case HelpEntryCreated = 'help_entry_created';
    case HelpEntryUpdated = 'help_entry_updated';
    case HelpEntryHidden = 'help_entry_hidden';
    case HelpGroupUpdated = 'help_group_updated';
}
