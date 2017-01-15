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

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Nonce.
 */
class Nonce
{
    /**
     * @var string
     */
    private $nonce;

    /** @var string */
    private $timestamp;

    /**
     * @param $nonce
     * @param $timestamp
     */
    public function __construct($nonce, $timestamp)
    {
        $this->nonce     = $nonce;
        $this->timestamp = $timestamp;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('oauth1_nonces')
            ->setCustomRepositoryClass('Mautic\ApiBundle\Entity\oAuth1\NonceRepository');

        $builder->createField('nonce', 'string')
            ->isPrimaryKey()
            ->build();

        $builder->addField('timestamp', 'string');
    }

    /**
     * @return mixed
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
