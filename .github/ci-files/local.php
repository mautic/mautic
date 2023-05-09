<?php

$parameters = [
    'api_enabled'                           => true,
    'api_enable_basic_auth'                 => true,
    'db_driver'                             => 'pdo_mysql',
    'db_host'                               => '127.0.0.1',
    'db_table_prefix'                       => null,
    'db_port'                               => getenv('DB_PORT'),
    'db_name'                               => 'mautictest',
    'db_user'                               => 'root',
    'db_password'                           => '',
    'admin_email'                           => 'github-actions@mautic.org',
    'admin_password'                        => 'GitHubActionsIsAwesome',
    'messenger_type'                        => 'async',
    'messenger_dsn'                         => 'in-memory://',
    'messenger_retry_strategy_max_retries'  => 3,
    'messenger_retry_strategy_delay'        => 1000,
    'messenger_retry_strategy_multiplier'   => 2,
    'messenger_retry_strategy_max_delay'    => 0,
];
