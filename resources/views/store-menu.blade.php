@extends('layouts.app', ['title' => 'Store Menu'])

@section('content')
    <x-page-header title="Store Menu" subtitle="Search by name to find a price - all figures in Philippine Pesos." />

    <div x-data="{
            search: '',
            items: @json(collect($menu)->flatMap(fn ($group) => collect($group['items'])->map(fn ($item) => ['category' => $group['category'], ...$item]))),
            get filtered() {
                const q = this.search.trim().toLowerCase();
                if (! q) return this.items;
                return this.items.filter(i => i.name.toLowerCase().includes(q));
            },
            get grouped() {
                const groups = {};
                for (const item of this.filtered) {
                    (groups[item.category] ??= []).push(item);
                }
                return groups;
            },
         }" class="max-w-lg">
        <div class="relative mb-6">
            <input type="search" x-model="search" placeholder="Search item, e.g. Gatorade"
                   class="block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 py-2.5 px-4"
                   autofocus>
        </div>

        <div x-show="filtered.length === 0" class="text-center text-slate-500 text-sm py-8">
            No item matches "<span x-text="search"></span>".
        </div>

        <template x-for="(categoryItems, category) in grouped" :key="category">
            <x-card class="mb-4 !p-4">
                <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2" x-text="category"></h2>
                <div class="divide-y divide-slate-100">
                    <template x-for="item in categoryItems" :key="item.name">
                        <div class="flex justify-between items-center py-2 text-sm">
                            <span class="text-slate-900" x-text="item.name"></span>
                            <span class="font-bold text-slate-900 tabular-nums" x-text="'₱' + item.price"></span>
                        </div>
                    </template>
                </div>
            </x-card>
        </template>
    </div>
@endsection
