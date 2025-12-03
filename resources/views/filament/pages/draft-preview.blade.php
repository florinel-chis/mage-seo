<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Draft Preview</h1>
            <div class="flex items-center space-x-4">
                <span class="text-lg font-semibold">Status: {{ $record->status }}</span>
                @if($record->status === 'PENDING_REVIEW')
                    <x-filament::button wire:click="approve">
                        Approve
                    </x-filament::button>
                    <x-filament::button color="danger" wire:click="reject">
                        Reject
                    </x-filament::button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                <h2 class="mb-4 text-xl font-bold">Original Content</h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold">Original Name</h3>
                        <p class="p-2 mt-1 bg-gray-100 rounded dark:bg-gray-700">{{ $record->original_data['name'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="font-semibold">Original Description</h3>
                        <p class="p-2 mt-1 bg-gray-100 rounded dark:bg-gray-700">{{ $record->original_data['description'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                <h2 class="mb-4 text-xl font-bold">Generated Draft</h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold">Meta Title</h3>
                        <p class="p-2 mt-1 bg-gray-100 rounded dark:bg-gray-700">{{ $record->generated_draft['meta_title'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="font-semibold">Meta Description</h3>
                        <p class="p-2 mt-1 bg-gray-100 rounded dark:bg-gray-700">{{ $record->generated_draft['meta_description'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>