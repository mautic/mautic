<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth2;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Model\Client as BaseClient;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;
use OAuth2\OAuth2;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Client.
 */
class Client extends BaseClient
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var ArrayCollection
     */
    protected $users;

    /**
     * @var string
     */
    protected $randomId;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var array
     */
    protected $redirectUris = [];

    /**
     * @var array
     */
    protected $allowedGrantTypes;

    /**
     *  Construct.
     */
    public function __construct()
    {
        parent::__construct();

        $this->allowedGrantTypes = [
            OAuth2::GRANT_TYPE_AUTH_CODE,
            OAuth2::GRANT_TYPE_REFRESH_TOKEN,
        ];

        $this->users = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('oauth2_clients')
            ->setCustomRepositoryClass('Mautic\ApiBundle\Entity\oAuth2\ClientRepository')
            ->addIndex(['random_id'], 'client_id_search');

        $builder->addIdColumns('name', false);

        $builder->createManyToMany('users', 'Mautic\UserBundle\Entity\User')
            ->setJoinTable('oauth2_user_client_xref')
            ->addInverseJoinColumn('user_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('client_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createField('randomId', 'string')
            ->columnName('random_id')
            ->build();

        $builder->addField('secret', 'string');

        $builder->createField('redirectUris', 'array')
            ->columnName('redirect_uris')
            ->build();

        $builder->createField('allowedGrantTypes', 'array')
            ->columnName('allowed_grant_types')
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            ['message' => 'mautic.core.name.required']
        ));

        $metadata->addPropertyConstraint('redirectUris', new Assert\NotBlank(
            ['message' => 'mautic.api.client.redirecturis.notblank']
        ));
    }

    /**
     * @var array
     */
    protected $changes;

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ($current != $val) {
            $this->changes[$prop] = [$current, $val];
        }
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Client
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);

        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectUris(array $redirectUris)
    {
        $this->isChanged('redirectUris', $redirectUris);

        $this->redirectUris = $redirectUris;
    }

    /**
     * Add authCodes.
     *
     * @param AuthCode $authCodes
     *
     * @return Client
     */
    public function addAuthCode(AuthCode $authCodes)
    {
        $this->authCodes[] = $authCodes;

        return $this;
    }

    /**
     * Remove authCodes.
     *
     * @param AuthCode $authCodes
     */
    public function removeAuthCode(AuthCode $authCodes)
    {
        $this->authCodes->removeElement($authCodes);
    }

    /**
     * Get authCodes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAuthCodes()
    {
        return $this->authCodes;
    }

    /**
     * Determines if a client attempting API access is already authorized by the user.
     *
     * @param User $user
     *
     * @return bool
     */
    public function isAuthorizedClient(User $user)
    {
        $users = $this->getUsers();

        return $users->contains($user);
    }

    /**
     * Add users.
     *
     * @param \Mautic\UserBundle\Entity\User $users
     *
     * @return Client
     */
    public function addUser(\Mautic\UserBundle\Entity\User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users.
     *
     * @param \Mautic\UserBundle\Entity\User $users
     */
    public function removeUser(\Mautic\UserBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
