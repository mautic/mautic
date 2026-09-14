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
            if (self::NAME_ID == $attributeName) {
                if ($assertion->getSubject() &&
                    $assertion->getSubject()->getNameID() &&
                    $assertion->getSubject()->getNameID()->getValue() &&
                    SamlConstants::NAME_ID_FORMAT_TRANSIENT != $assertion->getSubject()->getNameID()->getFormat()
                ) {
                    return $assertion->getSubject()->getNameID()->getValue();
                }
            } else {
                foreach ($assertion->getAllAttributeStatements() as $attributeStatement) {
                    $attribute = $attributeStatement->getFirstAttributeByName($attributeName);
                    if ($attribute && $attribute->getFirstAttributeValue()) {
                        return $attribute->getFirstAttributeValue();
                    }
                }
            }
        }

        return null;
    }
}
