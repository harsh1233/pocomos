@component('mail::message')


{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach


@endcomponent
