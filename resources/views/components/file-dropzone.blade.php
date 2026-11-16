@props([
    'name' => 'file',
    'accept' => null,
    'multiple' => false,
])

<div
    {{ $attributes->class([
        'file-dropzone',
    ]) }}
    data-file-dropzone
>
    <label
        class="file-dropzone-target text-center align-items-center justify-content-center h-100 w-100 pt-2"
    >
        <input
            type="file"
            name="{{ $name }}"
            @if ($accept) accept="{{ $accept }}" @endif
            @if ($multiple) multiple @endif
            class="visually-hidden"
            data-file-input
        >
        {{ $slot }}
    </label>

    <div
        class="h-100 w-100 d-flex align-items-center flex-wrap gap-2 p-2 {{ $multiple ? 'mt-3' : '' }}"
        data-file-preview
        aria-live="polite"
    ></div>
</div>
