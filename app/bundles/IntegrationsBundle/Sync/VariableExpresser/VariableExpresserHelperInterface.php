<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\VariableExpresser;

use Mautic\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

interface VariableExpresserHelperInterface
{
    public function decodeVariable(EncodedValueDAO $EncodedValueDAO): NormalizedValueDAO;

    /**
     * @param mixed $var
     */
    public function encodeVariable($var): EncodedValueDAO;
}
