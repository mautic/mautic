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
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class DoNotEmail
 *
 * @package Mautic\EmailBundle\Entity
 *
 * @Serializer\ExclusionPolicy("all")
 */
class DoNotEmail
{

    /**
     * @var int
     *
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $id;

    /**
     * @var Email
     *
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $email;

    /**
     * @var string
     *
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $emailAddress;

    /**
     * @var \Mautic\CampaignBundle\Entity\LeadRepository
     *
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $lead;

    /**
     * @var \DateTime
     *
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $dateAdded;

    /**
     * @var bool
     */
    private $unsubscribed = false;

    /**
     * @var bool
     */
    private $bounced = false;

    /**
     * @var string
     *
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $comments;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('email_donotemail')
            ->setCustomRepositoryClass('Mautic\CoreBundle\Entity\NotificationRepository');

        $builder->addId();

        $builder->createManyToOne('email', 'Email')
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createField('emailAddress', 'string')
            ->columnName('address')
            ->build();

        $builder->addLead();

        $builder->addDateAdded();

        $builder->addField('unsubscribed', 'boolean');

        $builder->addField('bounced', 'boolean');

        $builder->createField('comments', 'text')
            ->nullable()
            ->build();
    }

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