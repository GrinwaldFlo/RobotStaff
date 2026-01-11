@component('mail::message')
# {{ __('mail.greeting', ['name' => $admin->name]) }}

{{ $emailBody }}

@component('mail::button', ['url' => route('admin.dashboard')])
{{ __('mail.view_dashboard') }}
@endcomponent

{{ __('mail.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
