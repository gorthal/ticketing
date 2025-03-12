<?php

use Webklex\PHPIMAP\IMAP;

return [

    /*
    |--------------------------------------------------------------------------
    | IMAP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la connexion au serveur IMAP
    |
    */

    'accounts' => [
        'default' => [
            'host'          => env('IMAP_HOST', 'imap.example.com'),
            'port'          => env('IMAP_PORT', 993),
            'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username'      => env('IMAP_USERNAME', 'tickets@example.com'),
            'password'      => env('IMAP_PASSWORD', 'password'),
            'protocol'      => env('IMAP_PROTOCOL', 'imap'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Account
    |--------------------------------------------------------------------------
    |
    | Compte IMAP par défaut à utiliser.
    |
    */

    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Options IMAP
    |--------------------------------------------------------------------------
    |
    | Options à utiliser lors de la connexion IMAP.
    |
    */

    'options' => [
        'fetch' => IMAP::FT_UID,
        'fetch_body' => true,
        'fetch_attachment' => true,
        'fetch_flags' => true,
        'message_key' => 'id',
        'fetch_order' => 'asc',
        'open' => [
            // 'DISABLE_AUTHENTICATOR' => 'GSSAPI'
        ],
        'decoder' => [
            'message' => [
                'subject' => 'utf-8'
            ],
            'attachment' => [
                'name' => 'utf-8'
            ]
        ],
        'common_folders' => [
            'root' => 'INBOX',
            'junk' => 'INBOX/Junk',
            'draft' => 'INBOX/Drafts',
            'sent' => 'INBOX/Sent',
            'trash' => 'INBOX/Trash',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proxy Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration du proxy, si nécessaire.
    |
    */

    'proxy' => [
        'socket' => null,
        'request_fulluri' => false,
        'username' => null,
        'password' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Masquage des identifiants
    |--------------------------------------------------------------------------
    |
    | Permettre la journalisation des identifiants.
    |
    */

    'mask_credentials' => true,
];
