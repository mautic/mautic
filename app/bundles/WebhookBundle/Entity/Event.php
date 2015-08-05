<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Mautic\WebhookBundle\Entity\Webhook;

/**
 * @ORM\Entity
 * @ORM\Table(name="webhook_events")
 * @ORM\Entity(repositoryClass="Mautic\WebhookBundle\Entity\EventRepository")
 */
class Event
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\WebhookBundle\Entity\Webhook", inversedBy="events")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $webhook;

    /**
     * @ORM\Column(name="event_type", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"webhookDetails", "webhookList"})
     */
    private $event_type;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * @param mixed $webhook
     */
    public function setWebhook($webhook)
    {
        $this->webhook = $webhook;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->event_type;
    }

    /**
     * @param mixed $event_type
     */
    public function setEventType($event_type)
    {
        $this->event_type = $event_type;
        return $this;
    }
}