<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\DataObject;

use Mautic\CoreBundle\Exception\InvalidValueException;
use Mautic\CoreBundle\Form\DataTransformer\BarStringTransformer;

/**
 * A value object representation of a contact field token.
 */
class ContactFieldToken
{
    const REGEX = '{contactfield=(.*?)}';

    /**
     * @var string
     */
    private $fullToken;

    /**
     * @var string
     */
    private $fieldAlias;

    /**
     * @var string|null
     */
    private $defaultValue;

    public function __construct(string $fullToken)
    {
        $this->fullToken = $fullToken;
        $this->parse($fullToken);
    }

    public function getFullToken(): string
    {
        return $this->fullToken;
    }

    public function getFieldAlias(): string
    {
        return $this->fieldAlias;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    private function parse(string $fullToken): void
    {
        preg_match('/'.self::REGEX.'/', $fullToken, $matches);

        if (empty($matches[1])) {
            throw new InvalidValueException("'{$fullToken}' is not a valid contact field token. A valid token example: '{contactfield=firstname|John}'");
        }

        $barStringTransformer = new BarStringTransformer();
        $array                = $barStringTransformer->reverseTransform($matches[1]);
        $this->fieldAlias     = $array[0];
        $this->defaultValue   = $array[1] ?? null;
    }
}
