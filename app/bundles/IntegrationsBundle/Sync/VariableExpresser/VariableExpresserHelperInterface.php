<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
