<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional;

use Mautic\SmsBundle\Entity\Sms;

trait CreateEntitiesTrait
{
    private function createAnSms(string $name, string $message, bool $isPublished = true, string $locale = 'en'): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage($message);
        $sms->setLanguage($locale);
        $sms->setIsPublished($isPublished);

        return $sms;
    }
}
