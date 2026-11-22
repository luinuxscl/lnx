<x-layouts::auth :title="__('Email verification')">
    <div class="mt-4 flex flex-col gap-6">
        <flux:text class="text-center">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </flux:text>

        @if (session('status') == 'verification-link-sent')
            {{-- Esmeralda, como la paleta semántica del producto; sin los hacks !important
                sobre green (crm#16). --}}
            <flux:text class="text-center font-medium text-emerald-600 dark:text-emerald-400">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </flux:text>
        @endif

        {{-- El contenedor colapsa al ancho del contenido si los forms no estiran:
            sin w-full los botones quedan compactos y rompen la fila de botones
            full-width del resto de pantallas auth (crm#16). --}}
        <div class="flex w-full flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Resend verification email') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
