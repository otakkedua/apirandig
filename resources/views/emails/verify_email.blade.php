@component('mail::message')
# Halo {{ $user->name }}

Terima kasih telah mendaftar di **RancangKode**.

Klik tombol di bawah ini untuk memverifikasi email Anda.

@component('mail::button', ['url' => $url])
Verifikasi Email
@endcomponent

Jika Anda tidak mendaftar, abaikan email ini.

Salam,
**RancangKode**
@endcomponent