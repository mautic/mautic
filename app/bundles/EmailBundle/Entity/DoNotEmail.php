<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class DoNotEmail
 * @ORM\Table(name="email_donotemail")
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class DoNotEmail
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Email")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $email;

    /**
     * @ORM\Column(name="address", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $emailAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\Lead", inversedBy="doNotEmail")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $lead;

    /**
     * @ORM\Column(name="date_added", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $dateAdded;

    /**
     * @ORM\Column(name="unsubscribed", type="boolean")
     */
    private $unsubscribed = false;

    /**
     * @ORM\Column(name="bounced", type="boolean")
     */
    private $bounced = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $comments;

    /**
     * @return mixed
     */
    public function getComments ()
    {
        return $this->comments;
    }

    /**
     * @param mixed $comments
     */
    public function setComments ($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return mixed
     */
    public function getEmail ()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail (Email $email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getLead ()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead (Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getEmailAddress ()
    {
        return $this->emailAddress;
    }

    /**
     * @param mixed $emailAddress
     */
    public function setEmailAddress ($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return mixed
     */
    public function getBounced ()
    {
        return $this->bounced;
    }

    /**
     * @param mixed $bounced
     */
    public function setBounced ($bounced = true)
    {
        $this->bounced = $bounced;
    }

    /**
     * @return mixed
     */
    public function getDateAdded ()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded ($dateAdded)
    {
        $this->dateAdded = $dateAdded;
    }

    /**
     * @return mixed
     */
    public function getUnsubscribed ()
    {
        return $this->unsubscribed;
    }

    /**
     * @param mixed $unsubscribed
     */
    public function setUnsubscribed ($unsubscribed = true)
    {
        $this->unsubscribed = $unsubscribed;
    }
}