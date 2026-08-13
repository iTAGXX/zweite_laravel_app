<x-layouts::app :title="__('Members')">
    <div class="flex h-full min-w-0 w-full max-w-full flex-1 flex-col gap-4 overflow-x-hidden rounded-xl">
        <x-ui.empty-state
            :heading="__('Members')"
            :text="__('Member management will appear here.')"
        />
    </div>
</x-layouts::app>
