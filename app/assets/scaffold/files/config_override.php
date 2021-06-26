<?php

$container->setParameter('kernel.logs_dir', '%kernel.root_dir%/../../var/logs');
$container->setParameter('mautic.cache_path', '%kernel.root_dir%/../../var/cache');
$container->setParameter('mautic.log_path', '%kernel.root_dir%/../../var/logs');
$container->setParameter('mautic.tmp_path', '%kernel.root_dir%/../../var/tmp');
$container->setParameter('mautic.mailer_spool_path', '%kernel.root_dir%/../../var/tmp');
