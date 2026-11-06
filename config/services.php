<?php

$overpassUrls = array_merge(
    [env('OPENSTREETMAP_OVERPASS_URL', 'https://overpass-api.de/api/interpreter')],
    array_map('trim', explode(',', env(
        'OPENSTREETMAP_OVERPASS_FALLBACK_URLS',
        'https://overpass.kumi.systems/api/interpreter,https://overpass.private.coffee/api/interpreter'
    )))
);

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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openstreetmap' => [
        'overpass_url' => env('OPENSTREETMAP_OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),
        'overpass_urls' => array_values(array_unique(array_filter($overpassUrls))),
    ],

];
