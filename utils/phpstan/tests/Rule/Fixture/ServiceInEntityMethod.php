<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\CoreBundle\Translation\Translator;

final class ServiceInEntityMethod extends CommonEntity
{
    /**
     * @return string[]
     */
    public function getRowStatusesPieChart(Translator $translator): array
    {
        return [$translator->trans('mautic.core.success')];
    }
}
