@component('mail::message')
# {{ __('mail.greeting', ['name' => $staff->first_name ?? $staff->username]) }}

{{ $emailBody }}

{{ __('mail.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
