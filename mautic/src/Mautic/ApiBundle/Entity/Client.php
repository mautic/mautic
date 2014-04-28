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
use Symfony\Component\Security\Core\User\UserInterface;
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
}
