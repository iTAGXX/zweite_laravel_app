@if ($errors->any())
    <div data-test="form-errors">
        <flux:callout variant="danger" icon="x-circle">
            <flux:callout.heading>{{ __('Please fix the highlighted errors.') }}</flux:callout.heading>
        </flux:callout>
    </div>
@endif
