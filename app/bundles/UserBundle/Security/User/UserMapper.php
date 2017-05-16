<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\User;

use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Protocol\Response;
use LightSaml\SamlConstants;
use LightSaml\SpBundle\Security\User\UsernameMapperInterface;
use Mautic\UserBundle\Entity\User;

class UserMapper implements UsernameMapperInterface
{
    const NAME_ID = '@name_id@';

    /** @var string[] */
    private $attributes;

    /**
     * UserMapper constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param Response $response
     *
     * @return string|null
     */
    public function getUsername(Response $response, $returnEntity = false)
    {
        $user = new User();

        foreach ($response->getAllAssertions() as $assertion) {
            $this->getValueFromAssertion($assertion, $user);
        }

        return ($returnEntity) ? $user : $user->getUsername();
    }

    /**
     * @param Assertion $assertion
     *
     * @return null|string
     */
    private function getValueFromAssertion(Assertion $assertion, User $user)
    {
        $attributes = [];
        foreach ($this->attributes as $key => $attributeName) {
            if (self::NAME_ID == $attributeName) {
                // Check for a populated username; default to email if empty
                if (!$user->getUsername()) {
                    if ($email = $user->getEmail()) {
                        $attributes['email'] = $assertion->getSubject()->getNameID()->getValue();
                    } elseif (
                        $assertion->getSubject() &&
                        $assertion->getSubject()->getNameID() &&
                        $assertion->getSubject()->getNameID()->getValue() &&
                        $assertion->getSubject()->getNameID()->getFormat() != SamlConstants::NAME_ID_FORMAT_TRANSIENT
                    ) {
                        $attributes['username'] = $assertion->getSubject()->getNameID()->getValue();
                    }
                }
            } else {
                foreach ($assertion->getAllAttributeStatements() as $attributeStatement) {
                    $attribute = $attributeStatement->getFirstAttributeByName($attributeName);
                    if ($attribute && $attribute->getFirstAttributeValue()) {
                        $attributes[$key] = $attribute->getFirstAttributeValue();
                    }
                }
            }
        }

        // use email as the user by default
        if (isset($attributes['email'])) {
            $user->setEmail($attributes['email']);
            $user->setUsername($attributes['email']);
        } elseif (isset($attributes['username'])) {
            $user->setUsername($attributes['username']);
        }

        if (isset($attributes['firstname'])) {
            $user->setFirstname($attributes['firstname']);
        }

        if (isset($attributes['lastname'])) {
            $user->setLastName($attributes['lastname']);
        }
    }
}
