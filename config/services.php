<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid'            => env('TWILIO_SID'),
        'auth_token'     => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from'  => env('TWILIO_WHATSAPP_FROM'),
    ],

    // ── Telegram Bot API (gratuito) ──────────────────────────────────────────
    // Crea un bot con @BotFather y copia el token aquí.
    // El cliente debe enviar /start al bot para registrar su chat_id.
    'telegram' => [
        'bot_token'    => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    ],

    // ── WhatsApp Cloud API — Meta (capa gratuita) ────────────────────────────
    // Panel: developers.facebook.com → WhatsApp → Configuración de la API
    'whatsapp_cloud' => [
        'phone_number_id' => env('WHATSAPP_CLOUD_PHONE_ID'),
        'token'           => env('WHATSAPP_CLOUD_TOKEN'),
    ],

    // ── Web Push (VAPID) — notificaciones al celular del cliente sin app ─────
    // Claves generadas con \Minishlink\WebPush\VAPID::createVapidKeys().
    // "subject" es el mailto/URL de contacto que exige el estándar VAPID.
    'webpush' => [
        'public_key'  => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject'     => env('VAPID_SUBJECT', 'mailto:admin@madbarzz.com'),
    ],

];
