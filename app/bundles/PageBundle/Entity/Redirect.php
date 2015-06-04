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
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class Redirect
 * @ORM\Table(name="page_redirects")
 * @ORM\Entity(repositoryClass="Mautic\PageBundle\Entity\RedirectRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Redirect extends FormEntity
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="redirect_id", type="string", length=25)
     */
    private $redirectId;

    /**
     * @ORM\Column(type="text")
     */
    private $url;

    /**
     * @ORM\Column(name="hits", type="integer")
     */
    private $hits = 0;

    /**
     * @ORM\Column(name="unique_hits", type="integer")
     */
    private $uniqueHits = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\EmailBundle\Entity\Email")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $email;

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
