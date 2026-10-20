@props([
    'name',
    'label' => null,
])

<i
    data-lucide="{{ $name }}"
    @if ($label)
        role="img"
        aria-label="{{ $label }}"
    @else
        aria-hidden="true"
    @endif
    {{ $attributes->merge(['class' => 'inline-block h-4 w-4 shrink-0']) }}
></i>
