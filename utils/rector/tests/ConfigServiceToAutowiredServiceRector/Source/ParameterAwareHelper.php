<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ParameterAwareHelper
{
    public function __construct(private TranslatorInterface $translator, private string $secret)
    {
    }
}
