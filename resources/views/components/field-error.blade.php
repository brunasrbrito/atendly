@props(['name'])
@error($name)
    <span class="field__error" id="{{ str_replace('.', '-', $name) }}-error">{{ $message }}</span>
@enderror
