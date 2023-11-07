<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semiblod text-xl text-gray-800 leading-tight">
            {{ __('Viewing Site: ' . $site->name) }}
        </h2>
        <h3 class="font-semibold text-xl text-gray-600 leading-tight">
            {{ $site->url }}
        </h3>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <ul>
                    <li>{{ $site->url }}</li>
                    <li>{{ $site->name }}</li>
                    <li>{{ $site->is_online ? 'Your site is online' : 'Your site is offline' }}</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>