<x-guest-layout>
    <!-- <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center py-4 sm:pt-0"> -->
    <div>
        @if (Route::has('login'))
            <div class="sticky top-0 right-0 px-6 py-8 z-10 bg-white">
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm text-gray-700 underline">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 underline">Log in</a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline">Register</a>
                    @endif
                @endauth
            </div>
        @endif
        <livewire:show-surveys />
    </div>
</x-guest-layout>
