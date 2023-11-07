<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semiblod text-xl text-gray-800 leading-tight">
            {{ __('Sites') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sites as $site)
                            <tr>
                                <td>{{ $site->name }}</td>
                                <td>{{ $site->url }}</td>
                                <td>{{ $site->is_online ? 'Online' : 'Offline' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>