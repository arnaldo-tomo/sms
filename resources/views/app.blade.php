<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- SEO & Metadata -->
        <title inertia>{{ config('app.name', 'SMS Gateway Manager') }}</title>
        <meta name="description" content="Plataforma profissional de gestão de SMS Gateway. Envie, receba e monitorize mensagens SMS através de múltiplos dispositivos Android de forma rápida e segura.">
        <meta name="keywords" content="sms gateway, sms gateway manager, enviar sms, android sms gateway, marketing sms, sms bulk, api sms">
        <meta name="author" content="Arnaldo Tomo">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="SMS Gateway Manager - Envio e Gestão de SMS">
        <meta property="og:description" content="Envie e monitorize mensagens SMS através de múltiplos dispositivos Android de forma rápida e segura com a nossa API.">
        <meta property="og:image" content="/favicon.svg">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:title" content="SMS Gateway Manager - Envio e Gestão de SMS">
        <meta property="twitter:description" content="Envie e monitorize mensagens SMS através de múltiplos dispositivos Android de forma rápida e segura com a nossa API.">
        <meta property="twitter:image" content="/favicon.svg">

        <!-- Favicons -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/png" href="/favicon.png" sizes="32x32">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
