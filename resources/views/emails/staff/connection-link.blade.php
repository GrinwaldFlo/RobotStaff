@component('mail::message')
# {{ __('mail.connection_link_greeting', ['name' => $staff->first_name ?? $staff->username]) }}

{{ __('mail.connection_link_intro') }}

@component('mail::button', ['url' => $loginUrl])
{{ __('mail.connection_link_button') }}
@endcomponent

{{ __('mail.connection_link_expire') }}

{{ __('mail.connection_link_ignore') }}

{{ __('mail.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
