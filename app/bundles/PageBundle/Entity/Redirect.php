<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class Redirect
 *
 * @package Mautic\PageBundle\Entity
 */
class Redirect extends FormEntity
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $redirectId;

    /**
     * @var
     */
    private $url;

    /**
     * @var int
     */
    private $hits = 0;

    /**
     * @var int
     */
    private $uniqueHits = 0;

    /**
     * @var ArrayCollection
     */
    private $trackables;

    /**
     * @deprecated to be removed in 2.0
     *
     * @var
     */
    private $email;

    /**
     * Redirect constructor.
     */
    public function __construct()
    {
        $this->trackables = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('page_redirects')
            ->setCustomRepositoryClass('Mautic\PageBundle\Entity\RedirectRepository');

        $builder->addId();

        $builder->createField('redirectId', 'string')
            ->columnName('redirect_id')
            ->length(25)
            ->build();

        $builder->addField('url', 'text');

        $builder->addField('hits', 'integer');

        $builder->createField('uniqueHits', 'integer')
            ->columnName('unique_hits')
            ->build();

        $builder->createOneToMany('trackables', 'Trackable')
            ->mappedBy('redirect')
            ->fetchExtraLazy()
            ->build();

        // @deprecated to be removed in 2.0
        $builder->addNamedField('email', 'integer', 'email_id', true);
    }

    /**
     * Prepares the metadata for API usage
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('redirect')
            ->addListProperties(
                array(
                    'id',
                    'redirectId',
                    'url'
                )
            )
            ->addProperties(
                array(
                    'hits',
                    'uniqueHits'
                )
            )
            ->build();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRedirectId()
    {
        return $this->redirectId;
    }

    /**
     * @param string $redirectId
     */
    public function setRedirectId($redirectId = null)
    {
        if ($redirectId === null) {
            $redirectId = substr(hash('sha1', uniqid(mt_rand())), 0, 25);
        }
        $this->redirectId = $redirectId;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Set hits
     *
     * @param integer $hits
     *
     * @return Page
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits
     *
     * @return integer
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set uniqueHits
     *
     * @param integer $uniqueHits
     *
     * @return Page
     */
    public function setUniqueHits($uniqueHits)
    {
        $this->uniqueHits = $uniqueHits;

        return $this;
    }

    /**
     * Get uniqueHits
     *
     * @return integer
     */
    public function getUniqueHits()
    {
        return $this->uniqueHits;
    }

    /**
     * @return ArrayCollection
     */
    public function getTrackableList()
    {
        return $this->trackables;
    }

    /**
     * @param ArrayCollection $trackables
     *
     * @return Redirect
     */
    public function setTrackables($trackables)
    {
        $this->trackables = $trackables;

        return $this;
    }

    /**
     * @deprecated to be removed in 2.0
     *
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @deprecated to be removed in 2.0
     *
     * @param mixed $email
     *
     * @return Redirect
     */
    public function setEmail($email)
    {
        if ($email instanceof Email) {
            $email = $email->getId();
        }
        
        $this->email = $email;

        return $this;
    }
}
