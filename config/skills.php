<?php

return [

    /*
    | Comma-separated hostnames allowed for custom HTTP webhook skills.
    | Empty = allow any (not recommended in production).
    */
    'http_webhook_allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS', ''))
    ))),

    'http_webhook_timeout_seconds' => (int) env('SKILLS_HTTP_WEBHOOK_TIMEOUT', 30),

    /*
    | When false, packages with is_premium=true cannot be installed until you
    | wire billing / entitlements (returns HTTP 402 from install).
    */
    'allow_premium_install_without_subscription' => filter_var(
        env('SKILLS_ALLOW_PREMIUM_WITHOUT_SUBSCRIPTION', 'true'),
        FILTER_VALIDATE_BOOL
    ),

];
