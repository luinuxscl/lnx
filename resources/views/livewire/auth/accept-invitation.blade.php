<x-layouts::auth :title="__('Accept invitation')">
    <div class="flex flex-col gap-6">
        <x-lnx-ui::auth-header :title="__('Accept invitation')" :description="__('Complete your account to join.')" />

        @if (auth()->check())
            <flux:callout variant="warning">{{ __('Sign out before accepting an invitation.') }}</flux:callout>
        @elseif (! $available)
            <flux:callout variant="warning">{{ __('This invitation is no longer available.') }}</flux:callout>
        @else
            <flux:text>{{ $invitation->email }}</flux:text>
            <form method="POST" action="{{ route('invitations.accept.store', ['invitation' => $invitation, 'token' => $token]) }}" class="flex flex-col gap-6">
                @csrf
                <flux:input name="name" :label="__('Name')" :value="old('name')" required autofocus autocomplete="name" />
                <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password" viewable />
                <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" />
                <flux:button type="submit" variant="primary">{{ __('Create account') }}</flux:button>
            </form>
        @endif

        @if ($errors->any())
            <flux:callout variant="danger">{{ $errors->first() }}</flux:callout>
        @endif
    </div>
</x-layouts::auth>
