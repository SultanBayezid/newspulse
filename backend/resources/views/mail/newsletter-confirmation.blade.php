<x-mail::message>
# Confirm your subscription

Hello {{ $name }},

Thanks for subscribing to the {{ config('app.name') }} newsletter. Before we start sending you the headlines, please confirm your email address by clicking the button below.

<x-mail::button :url="$confirmUrl">
Confirm subscription
</x-mail::button>

If you did not request this subscription, you can safely ignore this email - nothing will be sent until you confirm.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
