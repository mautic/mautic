<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth1;

use Bazinga\OAuthServerBundle\Model\Consumer as BaseConsumer;
use Bazinga\OAuthServerBundle\Util\Random;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Entity(repositoryClass="Mautic\ApiBundle\Entity\oAuth1\ConsumerRepository")
 * @ORM\Table(name="oauth1_consumers")
 * @ORM\HasLifecycleCallbacks
 */
class Consumer extends BaseConsumer
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
            array('message' => 'mautic.api.client.name.notblank')
        ));

        $metadata->addPropertyConstraint('callback', new Assert\NotBlank(
            array('message' => 'mautic.api.client.callback.notblank')
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
}
