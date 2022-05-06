<?php

namespace Mautic\UserBundle\Security\SAML\User;

use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Protocol\Response;
use LightSaml\SpBundle\Security\User\UsernameMapperInterface;
use Mautic\UserBundle\Entity\User;

class UserMapper implements UsernameMapperInterface
{
    /**
     * @var string[]
     */
    private $attributes;

    /**
     * UserMapper constructor.
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getUser(Response $response): User
    {
        $user = new User();

        foreach ($response->getAllAssertions() as $assertion) {
            $this->setValuesFromAssertion($assertion, $user);
        }

        return $user;
    }

    public function getUsername(Response $response): ?string
    {
        $user = $this->getUser($response);

        return $user->getUsername();
    }

    /**
     * @return string|null
     */
    private function setValuesFromAssertion(Assertion $assertion, User $user): void
    {
        $attributes = $this->extractAttributes($assertion);

        // use email as the user by default
        if (isset($attributes['email'])) {
            $user->setEmail($attributes['email']);
            $user->setUsername($attributes['email']);
        }

        if (isset($attributes['username']) && !empty($attributes['username'])) {
            $user->setUsername($attributes['username']);
        }

        if (isset($attributes['firstname'])) {
            $user->setFirstname($attributes['firstname']);
        }

        if (isset($attributes['lastname'])) {
            $user->setLastName($attributes['lastname']);
        }
    }

    private function extractAttributes(Assertion $assertion): array
    {
        $attributes = [];

        foreach ($this->attributes as $key => $attributeName) {
            foreach ($assertion->getAllAttributeStatements() as $attributeStatement) {
                $attribute = $attributeStatement->getFirstAttributeByName($attributeName);
                if ($attribute && $attribute->getFirstAttributeValue()) {
                    $attributes[$key] = $attribute->getFirstAttributeValue();
                }
            }
        }

        return $attributes;
    }
}
