<?php

return [
    'parameters' => [
        'messenger_dsn_email'                  => 'sync://', // sync means no queue
        'messenger_dsn_hit'                    => 'sync://', // sync means no queue
        'messenger_dsn_failed'                 => null, // failed transport is optional
        'messenger_retry_strategy_max_retries' => 3, // Maximum number of retries for a failed send
        'messenger_retry_strategy_delay'       => 1000, // Delay in milliseconds between retries
        'messenger_retry_strategy_multiplier'  => 2.0, // Delay multiplier between retries  e.g. 1 second delay, 2 seconds, 4 seconds
        'messenger_retry_strategy_max_delay'   => 0, // maximum delay in milliseconds between retries
    ],
];
