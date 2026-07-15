<?php

declare(strict_types=1);

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\SmsBundle\Model\SmsModel;
use Rector\Config\RectorConfig;
use Utils\Rector\GetModelToRequiredPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(GetModelToRequiredPropertyRector::class, [
        'sms'   => SmsModel::class,
        'email' => EmailModel::class,
    ]);
};
