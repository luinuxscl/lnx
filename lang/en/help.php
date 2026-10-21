<?php

return [
    'groups' => [
        'getting_started' => 'Getting started',
        'account' => 'Account and access',
        'collaboration' => 'Users and collaboration',
        'support' => 'Support',
        'ai' => 'AI and knowledge',
        'operations' => 'Operations',
        'access' => 'Access',
        'packages' => 'Packages',
    ],

    'guides' => [
        'getting_started' => [
            'title' => 'Getting started and navigation',
            'summary' => 'Learn the main areas of lnx and how to move between them.',
            'body' => <<<'MD'
## The main screen

Signing in takes you to the [Dashboard](/dashboard). The sidebar holds the application features and the bottom menu groups the tools for your role.

1. Use the **main menu** to return to the Dashboard.
2. Use the **bottom menu** to open the tools for your role.
3. On desktop, the top bar shows the breadcrumbs for the current page.

## Help and account

- The **Help** button in the header opens this center, the FAQ and support.
- The language switcher changes the interface without signing you out.
- The user menu at the bottom of the sidebar opens settings and sign-out.

## Good practices

- Keep your profile up to date.
- Set up a passkey or 2FA before sharing your account.
- Check this guide when a new module appears in the menu.
MD,
        ],

        'dashboard' => [
            'title' => 'Dashboard',
            'summary' => 'Read the indicators and shortcuts on your panel.',
            'body' => <<<'MD'
## What the Dashboard shows

The [Dashboard](/dashboard) combines usage indicators, recent activity and shortcuts. The content depends on your role: root and admin see administration metrics; everyone else sees their own summary.

1. Review the top cards to spot usage changes.
2. Open a card to go to the related section.
3. Use the sidebar when a feature is not among the shortcuts.

## If an indicator looks wrong

Data is calculated with the timezone saved in your profile. If the range is not what you expect, review **Language, timezone and appearance**.
MD,
        ],

        'profile_security' => [
            'title' => 'Profile, security, passkeys and 2FA',
            'summary' => 'Protect your account and keep your contact details current.',
            'body' => <<<'MD'
## Profile

1. Open [Profile](/settings/profile).
2. Update your name, email and photo.
3. Save the changes and check the confirmation message.

## Security

In [Security](/settings/security) you can change your password, revoke sessions and manage two-factor authentication.

## Passkeys

1. Press **Add passkey**.
2. Follow the browser or operating system instructions.
3. Sign out and test the passkey before relying on it.

## 2FA

1. Enable two-factor authentication.
2. Scan the code with your authenticator app.
3. Store the recovery codes in a safe place.

> If you lose the device, use a recovery code or contact root to restore access.
MD,
        ],

        'notifications' => [
            'title' => 'Notifications',
            'summary' => 'Review the bell alerts and mark them as read.',
            'body' => <<<'MD'
## The bell

The header bell shows unread notifications. When you open it you can:

1. Read the message and its link.
2. Open the related resource.
3. Mark the notification as read.

The [Notifications](/notifications) page keeps the full history.

## What sends a notification

- Role changes and administrative alerts.
- Replies and status changes on your support requests.
- Events from installed modules that declare their own notification type.

If a notification does not open the expected resource, the module that sends it has not declared its link yet; tell root.
MD,
        ],

        'users_invitations' => [
            'title' => 'Users and invitations',
            'summary' => 'Invite accounts, assign roles and manage team access.',
            'body' => <<<'MD'
## Invite a person

1. Open [Users](/admin/users).
2. Press **Invite** and enter the email address.
3. Send the invitation and share the link if the person does not receive it.

## Assign roles

The available roles are **admin** and standard user. Root is never assigned from the interface.

1. Open the user record.
2. Select the role.
3. Save and confirm the change in the list.

## Suspend and reactivate

A suspended account keeps its data but cannot sign in. Reactivate it from the same record.

> Some account-security actions are reserved for root. Confirm with root before changing another person's access.
MD,
        ],

        'feedback_support' => [
            'title' => 'Feedback and support',
            'summary' => 'Send a request, attach a screenshot and follow its status.',
            'body' => <<<'MD'
## Create a request

1. Open the **Help** button in the header and choose **Contact support**.
2. Pick the type: problem, improvement idea or question.
3. Describe what you expected and what happened.
4. Attach a screenshot if it helps explain the case.
5. Send the request.

The panel records the screen, version and technical context to speed up diagnosis. Never include passwords or sensitive data.

## Follow the status

In [My requests](/support?mine=1) you can see your tickets and their status: received, in progress, waiting for you, resolved or closed. You will get a notification when support replies.

## Reopen a case

If a resolved case still affects you, open it and use **Reopen request** to explain what is missing.
MD,
        ],

        'chat' => [
            'title' => 'Chat and text assistant',
            'summary' => 'Use chat to ask about the product and write better requests.',
            'body' => <<<'MD'
## Open chat

1. Go to [Chat](/chat).
2. Type your question and send it.
3. Review the answer and the cited sources when available.

## Text assistant

The assistant can improve the wording of a support request without changing its meaning. Only the description text is sent; images are never sent to AI.

1. Write the draft in the support panel.
2. Press **Improve writing with AI**.
3. Review the suggestion and accept or discard it.

## Limits

Chat and the assistant depend on the AI configuration of the installation. If they are disabled, root must review the configuration and quota.
MD,
        ],

        'knowledge' => [
            'title' => 'Knowledge and RAG',
            'summary' => 'Organize manuals and documents for answers with references.',
            'body' => <<<'MD'
## Upload knowledge

1. Open [Knowledge](/knowledge).
2. Press **Add document**.
3. Select the file and wait for extraction.
4. Review the extracted text and confirm the document.

## Knowledge bases

Bases group documents by audience. Use [Bases](/knowledge/bases) to create, activate or deactivate a base.

## How chat uses it

Chat queries the active bases and cites the retrieved fragments. If an answer does not cite the expected source:

1. Confirm the document is confirmed and active.
2. Check that the base is attached to the conversation.
3. Ask again using terms from the document.

> Extraction accepts PDF with selectable text, TXT, MD, JSON and CSV, up to 10 MB.
MD,
        ],

        'documents' => [
            'title' => 'Documents',
            'summary' => 'Extract data from files and review the result before confirming it.',
            'body' => <<<'MD'
## Extract a document

1. Open [Document AI](/documents).
2. Upload the file and wait for processing.
3. Review the extracted fields and correct anything that is wrong.
4. Confirm the document to keep the result.

## States

- **Pending**: the file was received and is waiting to be processed.
- **Processing**: extraction is running.
- **Review required**: some fields are uncertain.
- **Confirmed**: the result was reviewed and saved.
- **Failed**: the file could not be processed.

Download the original from the detail page if you need to compare it with the extraction.
MD,
        ],

        'emails' => [
            'title' => 'Sent emails',
            'summary' => 'Review the transactional email history and its errors.',
            'body' => <<<'MD'
## Check the history

1. Open [Sent emails](/admin/emails).
2. Filter by status or search by recipient or subject.
3. Select an email to see its content and the error, if it failed.

## Good practices

- A failed send is not retried automatically.
- Fix the cause and trigger the action that generates the email again.
- Do not resend emails with sensitive data to the wrong recipients.

The history is read-only and available to root.
MD,
        ],

        'packages' => [
            'title' => 'Packages',
            'summary' => 'Check installed versions and update the app modules.',
            'body' => <<<'MD'
## Installed packages

1. Open [Packages](/admin/packages).
2. Review the installed and latest available versions.
3. Copy the update command when a new version is available.

## Update

1. Make a backup or confirm the deployment has one.
2. Run the update command on the server.
3. Review migrations, cache and error logs.
4. Check the package on this page after the deployment.

## UI components

The [UI/UX components](/admin/packages/lnx-ui) page shows the shared visual package gallery and its version.
MD,
        ],

        'activity' => [
            'title' => 'Administrative activity',
            'summary' => 'Audit user, navigation and configuration changes.',
            'body' => <<<'MD'
## Review activity

1. Open [Activity](/admin/activity).
2. Filter by actor, action or date range.
3. Open the detail to see the previous and new values.

## What it is for

- Reconstruct who changed a role or a setting.
- Confirm that an administrative action completed.
- Investigate an unexpected access or change.

Activity is immutable from the interface and only root can read it.
MD,
        ],

        'setup' => [
            'title' => 'Setup checklist',
            'summary' => 'Complete the minimum configuration before opening the app.',
            'body' => <<<'MD'
## Complete the checklist

1. Open [Checklist](/admin/setup).
2. Review each pending item.
3. Press the task link to go to the matching section.
4. Return to the checklist and confirm the item is complete.

## Common items

- Development root credentials replaced.
- Outbound email configured.
- AI configured or deliberately disabled.
- Roles and first accounts reviewed.
- Navigation and packages checked.

The checklist does not block the application: it is an operations guide for root.
MD,
        ],

        'preferences' => [
            'title' => 'Language, timezone and appearance',
            'summary' => 'Adjust the interface and date format to the way you work.',
            'body' => <<<'MD'
## Change preferences

1. Open [Appearance](/settings/appearance).
2. Choose language, timezone, theme and color scheme.
3. Save the changes.

Preferences are stored per account and applied on every device where you sign in.

## Language

The header switcher changes the language instantly. If a translation is missing, the interface shows the English source string.

## Dates and times

Dates are shown in your timezone. If a report does not match your local time, check this preference first.
MD,
        ],

        'general_settings' => [
            'title' => 'General settings',
            'summary' => 'Define the installation identity, email and shared values.',
            'body' => <<<'MD'
## Open the settings

1. Go to [General settings](/admin/settings).
2. Review the site identity, outbound email and shared options.
3. Save and send a test email if you changed the transport.

## Good practices

- Change one section at a time.
- Test email before announcing the change.
- Review [Activity](/admin/activity) if a value was not saved as expected.

These settings are exclusive to root.
MD,
        ],

    ],

    'faq' => [
        'reset_password' => [
            'title' => 'How do I reset my password?',
            'summary' => 'Use the recovery link from the sign-in page.',
            'body' => 'On the sign-in screen press **Forgot your password?**, enter your email and open the link you receive. The link expires; if it does not arrive, check spam or ask root to confirm your email.',
        ],

        'change_email' => [
            'title' => 'How do I change my email?',
            'summary' => 'Update the email from your profile and verify the change.',
            'body' => 'Open [Profile](/settings/profile), enter the new email and save. If verification is enabled, confirm the message sent to the new address before signing out.',
        ],

        'passkeys' => [
            'title' => 'What if I lose my passkey?',
            'summary' => 'Use another sign-in method or a recovery code.',
            'body' => 'If you are still signed in, remove the lost passkey in [Security](/settings/security) and register a new one. If you cannot sign in, use 2FA or ask root to review your access. Never share your recovery codes.',
        ],

        'two_factor' => [
            'title' => 'Where should I store 2FA codes?',
            'summary' => 'Keep them away from the device that holds the second factor.',
            'body' => 'Download or copy the recovery codes when you enable 2FA and store them in a password manager or a safe physical place. Each code works only once.',
        ],

        'language' => [
            'title' => 'How do I change the language or timezone?',
            'summary' => 'Adjust both preferences from Appearance.',
            'body' => 'Use the header language switcher or open [Appearance](/settings/appearance). The timezone is stored per account and affects the dates you see in the application.',
        ],

        'notifications' => [
            'title' => 'Why am I not receiving notifications?',
            'summary' => 'Check the bell, browser settings and the event type.',
            'body' => 'Open [Notifications](/notifications) to confirm the event was recorded. App notifications are internal: if you expected an email, check that outbound email is configured and that your address is correct.',
        ],

        'contact_support' => [
            'title' => 'How do I contact support?',
            'summary' => 'Create a request from the Help button.',
            'body' => 'Open **Help → Contact support**, describe the problem and attach a screenshot if useful. The panel automatically adds the screen, version and technical context to speed up diagnosis.',
        ],

        'request_status' => [
            'title' => 'Where do I see my request status?',
            'summary' => 'Check your tickets in My requests.',
            'body' => 'Go to [My requests](/support?mine=1). Each ticket shows its status and the conversation. You will get a notification when support replies or changes the status.',
        ],

        'ai_disabled' => [
            'title' => 'Why is AI disabled?',
            'summary' => 'The feature depends on the installation configuration and quota.',
            'body' => 'AI is enabled from the root configuration. If chat or the assistant does not respond, check with root that the provider is configured, the model is allowed and the quota has not run out.',
        ],

        'ai_cost' => [
            'title' => 'How do I review AI usage?',
            'summary' => 'Check the recorded attempts, tokens and cost.',
            'body' => 'Open [AI usage](/admin/ai/usage). Each attempt records the model, tokens, cost, duration and status. Filter by date or user to spot spikes before they exhaust the quota.',
        ],

        'install_package' => [
            'title' => 'How do I install a new package?',
            'summary' => 'Install with Composer, run migrations and check the packages page.',
            'body' => 'Install the package with Composer on the server, run migrations and clear the cache. The package appears in [Packages](/admin/packages) only when it declares its metadata in the registry. Follow the deployment procedure of the installation.',
        ],

        'package_permissions' => [
            'title' => 'Why does a module not appear in the menu?',
            'summary' => 'The package must declare its route, permissions and metadata.',
            'body' => 'A module is not published just because it sits in `vendor/`. It must declare its permissions, menu entry and, when relevant, its help content. After installing it, run the navigation sync and review [Navigation](/admin/navigation).',
        ],

        'privacy' => [
            'title' => 'What data is sent to AI or support?',
            'summary' => 'Only the necessary text is sent, and images never go to AI.',
            'body' => 'The support assistant sends only the description you write; images stay attached to the ticket and are never sent to AI. Chat sends your message and the context of the attached knowledge bases. Do not include passwords, tokens or unnecessary personal data.',
        ],

        'invite_user' => [
            'title' => 'How do I invite someone to the team?',
            'summary' => 'Send an invitation from user administration.',
            'body' => 'Open [Users](/admin/users), press **Invite**, enter the email and send it. The person receives a link to create their account. If the invitation expires, generate a new one.',
        ],

        'suspend_user' => [
            'title' => 'What happens when an account is suspended?',
            'summary' => 'The account keeps its data but cannot sign in.',
            'body' => 'Suspension revokes access without deleting data or history. You can reactivate the account from its record. Review the reason before suspending and record the change in the team internal conversation.',
        ],
    ],
];
