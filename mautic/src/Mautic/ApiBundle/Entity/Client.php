<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Table(name="oauth_clients")
 * @ORM\Entity(repositoryClass="Mautic\ApiBundle\Entity\ClientRepository")
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="text", length=50, nullable=true)
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinTable(name="oauth_user_client_xref")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $users;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            array('message' => 'mautic.api.client.name.notblank')
        ));

        $metadata->addPropertyConstraint('redirectUris', new Assert\NotBlank(
            array('message' => 'mautic.api.client.redirecturis.notblank')
        ));
    }

        /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Client
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
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
        $this->redirectUris = $redirectUris;
    }

    /**
     * Add authCodes
     *
     * @param \Mautic\ApiBundle\Entity\AuthCode $authCodes
     * @return Client
     */
    public function addAuthCode(\Mautic\ApiBundle\Entity\AuthCode $authCodes)
    {
        $this->authCodes[] = $authCodes;

        return $this;
    }

    /**
     * Remove authCodes
     *
     * @param \Mautic\ApiBundle\Entity\AuthCode $authCodes
     */
    public function removeAuthCode(\Mautic\ApiBundle\Entity\AuthCode $authCodes)
    {
        $this->authCodes->removeElement($authCodes);
    }

    /**
     * Get authCodes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAuthCodes()
    {
        return $this->authCodes;
    }


    /**
     * Determines if a client attempting API access is already authorized by the user
     *
     * @param User $user
     * @return bool
     */
    public function isAuthorizedClient(User $user)
    {
        $users = $this->getUsers();
        return $users->contains($user);
    }

    /**
     * Add users
     *
     * @param \Mautic\UserBundle\Entity\User $users
     * @return Client
     */
    public function addUser(\Mautic\UserBundle\Entity\User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Mautic\UserBundle\Entity\User $users
     */
    public function removeUser(\Mautic\UserBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
