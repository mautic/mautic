<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth1;

use Bazinga\OAuthServerBundle\Model\ConsumerInterface;
use Bazinga\OAuthServerBundle\Util\Random;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Entity(repositoryClass="Mautic\ApiBundle\Entity\oAuth1\ConsumerRepository")
 * @ORM\Table(name="oauth1_consumers")
 * @ORM\HasLifecycleCallbacks
 */
class Consumer implements ConsumerInterface
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     */
    protected $consumerKey;

    /**
     * @ORM\Column(type="string")
     */
    protected $consumerSecret;

    /**
     * @ORM\Column(type="string")
     */
    protected $callback;

    /**
     * @ORM\OneToMany(targetEntity="AccessToken", mappedBy="consumer", indexBy="id", fetch="EXTRA_LAZY")
     */
    protected $accessTokens;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            array('message' => 'mautic.core.name.required')
        ));
    }

    /**
     * Proxy to get consumer key
     *
     * @return mixed
     */
    public function getRandomId()
    {
        return $this->consumerKey;
    }

    /**
     * Proxy to consumer key
     *
     * @return mixed
     */
    public function getPublicId()
    {
        return $this->consumerKey;
    }

    /**
     * Proxy to consumer secret
     *
     * @return mixed
     */
    public function getSecret()
    {
        return $this->consumerSecret;
    }

    /**
     * @ORM\PrePersist
     */
    public function createConsumerKeys()
    {
        if (empty($this->consumerKey)) {
            $this->consumerKey    = Random::generateToken();
            $this->consumerSecret = Random::generateToken();
        }
    }

    /**
     * Proxy to callback
     *
     * @return array
     */
    public function getRedirectUris()
    {
        return array($this->callback);
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * {@inheritDoc}
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    /**
     * {@inheritDoc}
     */
    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * {@inheritDoc}
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }
}
