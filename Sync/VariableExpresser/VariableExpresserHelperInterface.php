<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\VariableExpresser;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

/**
 * Interface VariableExpresserHelperInterface
 */
interface VariableExpresserHelperInterface
{
    /**
     * @param EncodedValueDAO $EncodedValueDAO
     *
     * @return NormalizedValueDAO
     */
    public function decodeVariable(EncodedValueDAO $EncodedValueDAO);

    /**
     * @param mixed $var
     *
     * @return EncodedValueDAO
     */
    public function encodeVariable($var);
}
