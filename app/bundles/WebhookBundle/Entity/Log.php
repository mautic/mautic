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

/**
 * Class Webhook
 * @ORM\Table(name="webhook_logs")
 * @ORM\Entity(repositoryClass="Mautic\WebhookBundle\Entity\LogRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Log
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\WebhookBundle\Entity\Webhook", inversedBy="logs")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $webhook;

    /**
     * @ORM\Column(name="statusCode", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"webhookDetails", "webhookList"})
     */
    private $statusCode;

    /**
     * @ORM\Column(name="date_added", type="datetime")
     **/
    private $dateAdded;

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
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
        return $this;
    }
}