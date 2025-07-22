<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Form\DataTransformer;

use Mautic\ConfigBundle\Form\Type\EscapeTransformer;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class DsnTransformerFactory
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private EscapeTransformer $escapeTransformer,
    ) {
    }

    public function create(string $configKey, bool $allowEmpty): DsnTransformer
    {
        return new DsnTransformer($this->coreParametersHelper, $this->escapeTransformer, $configKey, $allowEmpty);
    }
}
