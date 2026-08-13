<x-layouts::error :title="__('You are offline')">
    <x-ui.error-state
        :heading="__('You are offline')"
        :text="__('Reconnect to continue. Changes cannot be saved while you are offline.')"
    />
</x-layouts::error>
