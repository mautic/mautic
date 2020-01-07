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
use Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;

final class VariableExpresserHelper implements VariableExpresserHelperInterface
{
    const TRUE_BOOLEAN_VALUE  = 'true';
    const FALSE_BOOLEAN_VALUE = 'false';

    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    public function __construct()
    {
        $this->valueNormalizer = new ValueNormalizer();
    }

    public function decodeVariable(EncodedValueDAO $encodedValueDAO): NormalizedValueDAO
    {
        $value = $encodedValueDAO->getValue();

        return $this->valueNormalizer->normalizeForMautic($encodedValueDAO->getType(), $value);
    }

    /**
     * @param mixed $var
     */
    public function encodeVariable($var): EncodedValueDAO
    {
        if (is_null($var)) {
            return new EncodedValueDAO(EncodedValueDAO::STRING_TYPE, '');
        }

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
            return new EncodedValueDAO(EncodedValueDAO::DATETIME_TYPE, $var->format('c'));
        }

        if (is_bool($var)) {
            return new EncodedValueDAO(
                EncodedValueDAO::BOOLEAN_TYPE,
                true === $var ? self::TRUE_BOOLEAN_VALUE : self::FALSE_BOOLEAN_VALUE
            );
        }

        throw new \InvalidArgumentException('Variable type for '.var_export($var, true).' not supported');
    }
}
