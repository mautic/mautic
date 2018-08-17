<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helpers\VariableExpressor;

use MauticPlugin\IntegrationsBundle\DAO\Value\EncodedValueDAO;

/**
 * Interface VariableExpresserHelperInterface
 */
interface VariableExpresserHelperInterface
{
    /**
     * @param EncodedValueDAO $EncodedValueDAO
     *
     * @return mixed
     */
    public function decodeVariable(EncodedValueDAO $EncodedValueDAO);

    /**
     * @param mixed $var
     *
     * @return EncodedValueDAO
     */
    public function encodeVariable($var);
}
