<x-layouts::app :title="__('UI kit')">
    <div class="flex min-w-0 max-w-full flex-col gap-8 overflow-x-hidden">
        <div>
            <flux:heading size="xl" level="1">{{ __('UI kit') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Flux building blocks for EquiFlow screens.') }}</flux:text>
        </div>

        <section class="flex min-w-0 flex-col gap-3" data-test="ui-kit-buttons">
            <flux:heading size="lg" level="2">{{ __('Button') }}</flux:heading>
            <div class="flex min-w-0 flex-wrap gap-2">
                <flux:button variant="primary" class="min-h-11">{{ __('Primary') }}</flux:button>
                <flux:button class="min-h-11">{{ __('Default') }}</flux:button>
                <flux:button variant="ghost" class="min-h-11">{{ __('Ghost') }}</flux:button>
            </div>
        </section>

        <section class="flex min-w-0 flex-col gap-3" data-test="ui-kit-input">
            <flux:heading size="lg" level="2">{{ __('Input') }}</flux:heading>
            <flux:input label="{{ __('Name') }}" placeholder="{{ __('Name') }}" />
        </section>

        <section class="flex min-w-0 flex-col gap-3" data-test="ui-kit-select">
            <flux:heading size="lg" level="2">{{ __('Select') }}</flux:heading>
            <flux:select label="{{ __('Status') }}" placeholder="{{ __('Choose a status') }}">
                <flux:select.option value="open">{{ __('Open') }}</flux:select.option>
                <flux:select.option value="closed">{{ __('Closed') }}</flux:select.option>
            </flux:select>
        </section>

        <section class="flex min-w-0 flex-col gap-3" data-test="ui-kit-modal">
            <flux:heading size="lg" level="2">{{ __('Modal') }}</flux:heading>
            <flux:modal.trigger name="ui-kit-modal">
                <flux:button class="min-h-11">{{ __('Open modal') }}</flux:button>
            </flux:modal.trigger>
            <flux:modal name="ui-kit-modal" class="min-w-0 md:w-96">
                <div class="space-y-4">
                    <flux:heading size="lg">{{ __('Modal') }}</flux:heading>
                    <flux:text>{{ __('Example dialog for the design system.') }}</flux:text>
                    <div class="flex">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="primary" class="min-h-11">{{ __('Close') }}</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        </section>

        <section class="flex min-w-0 flex-col gap-3" data-test="ui-kit-table">
            <flux:heading size="lg" level="2">{{ __('Table') }}</flux:heading>
            <div class="min-w-0 overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Name') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>{{ __('Demo') }}</flux:table.cell>
                            <flux:table.cell>{{ __('Open') }}</flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
            </div>
        </section>

        <section class="flex min-w-0 flex-col gap-3" data-test="ui-kit-states">
            <flux:heading size="lg" level="2">{{ __('States') }}</flux:heading>
            <x-ui.empty-state :heading="__('No records')" :text="__('Nothing to show yet.')" />
            <x-ui.error-state :heading="__('Something went wrong')" :text="__('Please try again.')" />
            <x-ui.loading-state />
        </section>
    </div>
</x-layouts::app>
