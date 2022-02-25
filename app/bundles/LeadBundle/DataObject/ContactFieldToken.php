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

use Mautic\CoreBundle\Form\DataTransformer\BarStringTransformer;
use Mautic\LeadBundle\Exception\InvalidContactFieldTokenException;

/**
 * A value object representation of a contact field token.
 */
class ContactFieldToken
{
    private string $fullToken;

    private string $fieldAlias;

    private ?string $defaultValue;

    /**
     * @throws InvalidContactFieldTokenException
     */
    public function __construct(string $fullToken)
    {
        $this->fullToken = $fullToken;
        $this->parse(trim($fullToken));
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
        preg_match('/^{contactfield=(.*?)}$/', $fullToken, $matches);

        if (empty($matches[1])) {
            throw new InvalidContactFieldTokenException("'{$fullToken}' is not a valid contact field token. A valid token example: '{contactfield=firstname|John}'");
        }

        $barStringTransformer = new BarStringTransformer();
        $array                = $barStringTransformer->reverseTransform($matches[1]);
        $this->fieldAlias     = $array[0];
        $this->defaultValue   = $array[1] ?? null;
    }
}
