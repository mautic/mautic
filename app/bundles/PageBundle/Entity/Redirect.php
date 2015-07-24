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
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
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
     * @var Email
     */
    private $email;

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

        $builder->createManyToOne('email', 'Mautic\EmailBundle\Entity\Email')
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
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
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param Email $email
     *
     * @return Redirect
     */
    public function setEmail(Email $email = null)
    {
        $this->email = $email;

        return $this;
    }
}
