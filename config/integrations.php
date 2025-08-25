<?php

    return [
        'rate_limit_per_minute' => env('INTEGRATIONS_RATE_LIMIT_PER_MINUTE', 60),
        'rate_limit_decay' => env('INTEGRATIONS_RATE_LIMIT_DECAY', 60),
    ];