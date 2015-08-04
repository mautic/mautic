<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Mautic\WebhookBundle\Entity\Event;

/**
 * Class Webhook
 * @ORM\Table(name="webhooks")
 * @ORM\Entity(repositoryClass="Mautic\WebhookBundle\Entity\WebhookRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Webhook extends FormEntity
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"webhookDetails", "webhookList"})
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"webhookDetails", "webhookList"})
     */
    private $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(name="webhook_url", type="string", nullable=true, length=255)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"webhookDetails"})
     */
    private $webhookUrl;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CategoryBundle\Entity\Category")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"webhookDetails", "webhookList"})
     **/
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="Mautic\WebhookBundle\Entity\Event", mappedBy="webhook", cascade={"persist", "remove"})
     */
    private $events;

    /*
     * Constructor
     */

    public function __construct() {
        $this->events  = new ArrayCollection();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new NotBlank(array(
            'message' => 'mautic.core.name.required'
        )));

        $metadata->addPropertyConstraint('webhookUrl',  new Assert\Url(
                array(
                    'message' => 'mautic.core.value.required',
                    'groups'  => array('webhookUrl')
                )
            )
        );
    }

    /**
     * @param \Symfony\Component\Form\Form $form
     *
     * @return array
     */
    public static function determineValidationGroups(\Symfony\Component\Form\Form $form)
    {
        $data   = $form->getData();
        $groups = array('Webhook');

        $webhookUrl = $data->getWebhookUrl();

        if ($webhookUrl) {
            $groups[] = 'webhookUrl';
        }

        return $groups;
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Webhook
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Webhook
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set webhookUrl
     *
     * @param string $webhookUrl
     *
     * @return Webhook
     */
    public function setWebhookUrl($webhookUrl) {
        $this->isChanged('webhookUrl', $webhookUrl);
        $this->webhookUrl = $webhookUrl;

        return $this;
    }

    /**
     * Get webhookUrl
     *
     * @return string
     */
    public function getWebhookUrl() {
        return $this->webhookUrl;
    }

    /**
     * Set category
     *
     * @param \Mautic\CategoryBundle\Entity\Category $category
     *
     * @return Webhook
     */
    public function setCategory(\Mautic\CategoryBundle\Entity\Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Mautic\CategoryBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return mixed
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param mixed $events
     */
    public function setEvents($events)
    {
        $this->events = $events;
        /**  @var \Mautic\WebhookBundle\Entity\Event $event */
        foreach ($events as $event) {
            $event->setWebhook($this);
        }
    }

    public function addEvent(Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    public function removeEvent(Event $event)
    {
        $this->events->removeElement($event);
    }
}