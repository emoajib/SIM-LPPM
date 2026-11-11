@props([
    'id',
    'title' => null,
    'action' => null,
    'method' => 'POST',
    'submitText' => 'Save',
    'cancelText' =>
 'Cancel',
    'wireIgnore' => true,
    'componentId' => null,
    'onSubmit' => null,
    'onCancel' =>
 null,
    'size' => 'lg', // sm, md, lg, xl
    'scrollable' => true,
    'showCloseButton' =>
 true,
])

@php
    $formId = $id . '-form';
    $methodField = '';
    $csrfField = '';

    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        $methodField = method_field($method);
    }

    if (strtoupper($method) === 'POST') {
        $csrfField = csrf_field();
    }
@endphp

<x-tabler.modal :id="$id" :title="$title" :wire-ignore="$wireIgnore" :component-id="$componentId" :size="$size"
    :scrollable="$scrollable" :close-button="$showCloseButton" centered="true" class="modal-form">
    <x-slot name="body">
        @if ($action)
            <form id="{{ $formId }}" wire:submit.prevent="handleSubmit" x-data="modalForm('{{ $id }}', '{{ $formId }}', '{{ $submitText }}')">
                {{ $csrfField }}
                {{ $methodField }}

                <div wire:ignore>
                    {{ $slot }}
                </div>
            </form>
        @else
            <div wire:ignore>
                {{ $slot }}
            </div>
        @endif
    </x-slot>

    @if ($submitText || $cancelText)
        <x-slot name="footer">
            @if ($cancelText)
                <button type="button" class="btn btn-light d-flex align-items-center gap-2" data-bs-dismiss="modal">
                    {{ $cancelText }}
                </button>
            @endif

            @if ($submitText)
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                    @if ($action) x-on:click="submitForm"
                    @elseif($onSubmit && $componentId)
                        x-on:click="
                            const component = window.Livewire?.find('{{ $componentId }}');
                            component?.call('{{ $onSubmit }}');
                        " @endif>
                    <span x-show="!loading" x-text="submitText || '{{ $submitText }}'"></span>
                    <span x-show="loading" class="me-1 spinner-border spinner-border-sm" role="status"></span>
                    <span x-show="loading">Saving...</span>
                </button>
            @endif
        </x-slot>
    @endif
</x-tabler.modal>
