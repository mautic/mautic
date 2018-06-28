<?php

namespace MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor;

use MauticPlugin\MauticIntegrationsBundle\DAO\VariableEncodeDAO;

/**
 * Class VariableExpressorHelper
 * @package MauticPlugin\MauticIntegrationsBundle\Services
 */
final class VariableExpressorHelper implements VariableExpressorHelperInterface
{
    const TRUE_BOOLEAN_VALUE = 'true';
    const FALSE_BOOLEAN_VALUE = 'false';

    public function decodeVariable(VariableEncodeDAO $variableEncodeDAO)
    {
        $value = $variableEncodeDAO->getValue();
        switch($variableEncodeDAO->getType()) {
            case VariableEncodeDAO::STRING_TYPE:
                return $value;
            case VariableEncodeDAO::INT_TYPE:
                return (int)$value;
            case VariableEncodeDAO::FLOAT_TYPE:
                return (float)$value;
            case VariableEncodeDAO::DATETIME_TYPE:
                return (new \DateTime())->setTimestamp($value);
            case VariableEncodeDAO::BOOLEAN_TYPE:
                return $value === self::TRUE_BOOLEAN_VALUE;
            default:
                throw new \InvalidArgumentException('Variable type not supported');
        }
    }

    /**
     * @param mixed $var
     * @return VariableEncodeDAO
     */
    public function encodeVariable($var)
    {
        if(is_int($var)) {
            return new VariableEncodeDAO(VariableEncodeDAO::INT_TYPE, (string) $var);
        }
        if(is_string($var)) {
            return new VariableEncodeDAO(VariableEncodeDAO::STRING_TYPE, (string) $var);
        }
        if(is_float($var)) {
            return new VariableEncodeDAO(VariableEncodeDAO::FLOAT_TYPE, (string) $var);
        }
        if(is_double($var)) {
            return new VariableEncodeDAO(VariableEncodeDAO::DOUBLE_TYPE, (string) $var);
        }
        if($var instanceof \DateTime) {
            return new VariableEncodeDAO(VariableEncodeDAO::DATETIME_TYPE, $var->getTimestamp());
        }
        if(is_bool($var)) {
            return new VariableEncodeDAO(
                VariableEncodeDAO::BOOLEAN_TYPE,
                $var === true ? self::TRUE_BOOLEAN_VALUE : self::FALSE_BOOLEAN_VALUE
            );
        }
        throw new \InvalidArgumentException('Variable type not supported');
    }
}
