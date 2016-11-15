<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth1;

use Bazinga\OAuthServerBundle\Model\ConsumerInterface;
use Bazinga\OAuthServerBundle\Util\Random;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Consumer.
 */
class Consumer implements ConsumerInterface
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
     * @var string
     */
    protected $consumerKey;

    /**
     * @var string
     */
    protected $consumerSecret;

    /**
     * @var string
     */
    protected $callback;

    /**
     * @var ArrayCollection
     */
    protected $accessTokens;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->accessTokens = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('oauth1_consumers')
            ->setCustomRepositoryClass('Mautic\ApiBundle\Entity\oAuth1\ConsumerRepository')
            ->addLifecycleEvent('createConsumerKeys', 'prePersist')
            ->addIndex(['consumer_key'], 'consumer_search');

        $builder->addIdColumns('name', false);

        $builder->createField('consumerKey', 'string')
            ->columnName('consumer_key')
            ->build();

        $builder->createField('consumerSecret', 'string')
            ->columnName('consumer_secret')
            ->build();

        $builder->addField('callback', 'string');

        $builder->createOneToMany('accessTokens', 'AccessToken')
            ->setIndexBy('id')
            ->mappedBy('consumer')
            ->fetchExtraLazy()
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
    }

    /**
     * Proxy to get consumer key.
     *
     * @return mixed
     */
    public function getRandomId()
    {
        return $this->consumerKey;
    }

    /**
     * Proxy to consumer key.
     *
     * @return mixed
     */
    public function getPublicId()
    {
        return $this->consumerKey;
    }

    /**
     * Proxy to consumer secret.
     *
     * @return mixed
     */
    public function getSecret()
    {
        return $this->consumerSecret;
    }

    /**
     * Create consumer keys.
     */
    public function createConsumerKeys()
    {
        if (empty($this->consumerKey)) {
            $this->consumerKey    = Random::generateToken();
            $this->consumerSecret = Random::generateToken();
        }
    }

    /**
     * Proxy to callback.
     *
     * @return array
     */
    public function getRedirectUris()
    {
        return [$this->callback];
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * {@inheritdoc}
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }
}
