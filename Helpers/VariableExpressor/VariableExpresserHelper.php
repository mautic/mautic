<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor;

use MauticPlugin\MauticIntegrationsBundle\DAO\Value\EncodedValueDAO;

/**
 * Class VariableExpresserHelper
 */
final class VariableExpresserHelper implements VariableExpresserHelperInterface
{
    const TRUE_BOOLEAN_VALUE = 'true';
    const FALSE_BOOLEAN_VALUE = 'false';

    /**
     * @param EncodedValueDAO $EncodedValueDAO
     *
     * @return bool|float|int|mixed|string|static
     */
    public function decodeVariable(EncodedValueDAO $EncodedValueDAO)
    {
        $value = $EncodedValueDAO->getValue();
        switch ($EncodedValueDAO->getType()) {
            case EncodedValueDAO::STRING_TYPE:
                return $value;
            case EncodedValueDAO::INT_TYPE:
                return (int) $value;
            case EncodedValueDAO::FLOAT_TYPE:
                return (float) $value;
            case EncodedValueDAO::DATETIME_TYPE:
                return (new \DateTime())->setTimestamp($value);
            case EncodedValueDAO::BOOLEAN_TYPE:
                return $value === self::TRUE_BOOLEAN_VALUE;
            default:
                throw new \InvalidArgumentException('Variable type not supported');
        }
    }

    /**
     * @param mixed $var
     *
     * @return EncodedValueDAO
     */
    public function encodeVariable($var)
    {
        if (is_int($var)) {
            return new EncodedValueDAO(EncodedValueDAO::INT_TYPE, (string) $var);
        }
        if (is_string($var)) {
            return new EncodedValueDAO(EncodedValueDAO::STRING_TYPE, (string) $var);
        }
        if (is_float($var)) {
            return new EncodedValueDAO(EncodedValueDAO::FLOAT_TYPE, (string) $var);
        }
        if (is_double($var)) {
            return new EncodedValueDAO(EncodedValueDAO::DOUBLE_TYPE, (string) $var);
        }
        if ($var instanceof \DateTime) {
            return new EncodedValueDAO(EncodedValueDAO::DATETIME_TYPE, $var->getTimestamp());
        }
        if (is_bool($var)) {
            return new EncodedValueDAO(
                EncodedValueDAO::BOOLEAN_TYPE,
                $var === true ? self::TRUE_BOOLEAN_VALUE : self::FALSE_BOOLEAN_VALUE
            );
        }
        throw new \InvalidArgumentException('Variable type not supported');
    }
}
