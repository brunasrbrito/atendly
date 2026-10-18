@props(['name'])
@error($name)
    <span class="field__error">{{ $message }}</span>
@enderror
