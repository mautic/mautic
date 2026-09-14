<?php

/*
 * This file is part of the LightSAML SP-Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SpBundle\Security\User;

use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Protocol\Response;
use LightSaml\SamlConstants;

class SimpleUsernameMapper implements UsernameMapperInterface
{
    public const NAME_ID = '@name_id@';

    /**
     * @param string[] $attributes
     */
    public function __construct(
        private array $attributes,
    )
    {
    }

    public function getUsername(Response $response): ?string
    {
        foreach ($response->getAllAssertions() as $assertion) {
            $username = $this->getUsernameFromAssertion($assertion);
            if ($username) {
                return $username;
            }
        }

        return null;
    }

    private function getUsernameFromAssertion(Assertion $assertion): ?string
    {
        foreach ($this->attributes as $attributeName) {
            $username = self::NAME_ID === $attributeName
                ? $this->getUsernameFromNameId($assertion)
                : $this->getUsernameFromAttribute($assertion, $attributeName);

            if ($username) {
                return $username;
            }
        }

        return null;
    }

    private function getUsernameFromNameId(Assertion $assertion): ?string
    {
        $nameId = $assertion->getSubject()?->getNameID();
        if (
            $nameId
            && $nameId->getValue()
            && SamlConstants::NAME_ID_FORMAT_TRANSIENT !== $nameId->getFormat()
        ) {
            return $nameId->getValue();
        }

        return null;
    }

    private function getUsernameFromAttribute(Assertion $assertion, string $attributeName): ?string
    {
        foreach ($assertion->getAllAttributeStatements() as $attributeStatement) {
            $attribute = $attributeStatement->getFirstAttributeByName($attributeName);
            if ($attribute && $attribute->getFirstAttributeValue()) {
                return $attribute->getFirstAttributeValue();
            }
        }

        return null;
    }
}
