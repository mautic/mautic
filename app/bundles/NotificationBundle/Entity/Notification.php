<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Notification.
 */
class Notification extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $language = 'en';

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $heading;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $button;

    /**
     * @var array
     */
    private $utmTags = [];

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var int
     */
    private $readCount = 0;

    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $lists;

    /**
     * @var ArrayCollection
     */
    private $stats;

    /**
     * @var string
     */
    private $notificationType = 'template';

    /**
     * @var bool
     */
    private $mobile = false;

    /**
     * @var array
     */
    private $mobileSettings;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var string
     */
    private $actionButtonIcon1;

    /**
     * @var string
     */
    private $actionButtonUrl1;

    /**
     * @var string
     */
    private $actionButtonText2;

    /**
     * @var string
     */
    private $actionButtonIcon2;

    /**
     * @var string
     */
    private $actionButtonUrl2;

    /**
     * @var string
     */
    private $icon;

    /**
     * @var string
     */
    private $image;

    /**
     * @var array
     */
    public function __clone()
    {
        $this->id        = null;
        $this->stats     = new ArrayCollection();
        $this->sentCount = 0;
        $this->readCount = 0;

        parent::__clone();
    }

    /**
     * Notification constructor.
     */
    public function __construct()
    {
        $this->lists = new ArrayCollection();
        $this->stats = new ArrayCollection();
    }

    /**
     * Clear stats.
     */
    public function clearStats()
    {
        $this->stats = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('push_notifications')
            ->setCustomRepositoryClass('Mautic\NotificationBundle\Entity\NotificationRepository');

        $builder->addIdColumns();

        $builder->createField('language', 'string')
            ->columnName('lang')
            ->build();

        $builder->createField('url', 'text')
            ->nullable()
            ->build();

        $builder->createField('heading', 'text')
            ->build();

        $builder->createField('message', 'text')
            ->build();

        $builder->createField('button', 'text')
            ->nullable()
            ->build();

        $builder->createField('utmTags', 'array')
            ->columnName('utm_tags')
            ->nullable()
            ->build();

        $builder->createField('notificationType', 'text')
            ->columnName('notification_type')
            ->nullable()
            ->build();

        $builder->addPublishDates();

        $builder->createField('readCount', 'integer')
            ->columnName('read_count')
            ->build();

        $builder->createField('sentCount', 'integer')
            ->columnName('sent_count')
            ->build();

        $builder->addCategory();

        $builder->createManyToMany('lists', 'Mautic\LeadBundle\Entity\LeadList')
            ->setJoinTable('push_notification_list_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('notification_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('stats', 'Stat')
            ->setIndexBy('id')
            ->mappedBy('notification')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('mobile', 'boolean')->build();

        $builder->createField('mobileSettings', 'array')->build();

        $builder->createField('ttl', 'integer')
            ->build();

        $builder->createField('priority', 'integer')
            ->build();

        $builder->createField('actionButtonIcon1', 'string')
            ->columnName('action_button_icon_1')
            ->length(512)
            ->nullable()
            ->build();

        $builder->createField('actionButtonUrl1', 'string')
            ->columnName('action_button_url_1')
            ->length(512)
            ->nullable()
            ->build();

        $builder->createField('actionButtonText2', 'string')
            ->columnName('action_button_text_2')
            ->length(512)
            ->nullable()
            ->build();

        $builder->createField('actionButtonIcon2', 'string')
            ->columnName('action_button_icon_2')
            ->length(512)
            ->nullable()
            ->build();

        $builder->createField('actionButtonUrl2', 'string')
            ->columnName('action_button_url_2')
            ->length(512)
            ->nullable()
            ->build();

        $builder->createField('icon', 'string')
            ->length(512)
            ->nullable()
            ->build();

        $builder->createField('image', 'string')
            ->length(512)
            ->nullable()
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

        $metadata->addConstraint(new Callback([
            'callback' => function (Notification $notification, ExecutionContextInterface $context) {
                $type = $notification->getNotificationType();
                if ($type == 'list') {
                    $validator = $context->getValidator();
                    $violations = $validator->validate(
                        $notification->getLists(),
                        [
                            new LeadListAccess(
                                [
                                    'message' => 'mautic.lead.lists.required',
                                ]
                            ),
                            new NotBlank(
                                [
                                    'message' => 'mautic.lead.lists.required',
                                ]
                            ),
                        ]
                    );

                    if (count($violations) > 0) {
                        $string = (string) $violations;
                        $context->buildViolation($string)
                            ->atPath('lists')
                            ->addViolation();
                    }
                }
            },
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('notification')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'heading',
                    'message',
                    'url',
                    'language',
                    'category',
                    'button',
                ]
            )
            ->addProperties(
                [
                    'actionButtonUrl1',
                    'actionButtonIcon1',
                    'actionButtonUrl2',
                    'actionButtonIcon2',
                    'actionButtonText2',
                    'utmTags',
                    'publishUp',
                    'publishDown',
                    'readCount',
                    'sentCount',
                    'ttl',
                    'priority',
                ]
            )
            ->build();
    }

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();

        if ($prop == 'category' || $prop == 'list') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get file alias.
     *
     * @return string
     */
    public function getFileAlias()
    {
        return 'notification-file-'.$this->id;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param $category
     *
     * @return $this
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * @param string $heading
     */
    public function setHeading($heading)
    {
        $this->isChanged('heading', $heading);
        $this->heading = $heading;
    }

    /**
     * @return string
     */
    public function getButton()
    {
        return $this->button;
    }

    /**
     * @param string $heading
     */
    public function setButton($button)
    {
        $this->isChanged('button', $button);
        $this->button = $button;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->isChanged('message', $message);
        $this->message = $message;
    }

    /**
     * @return array
     */
    public function getUtmTags()
    {
        return $this->utmTags;
    }

    /**
     * @param array $utmTags
     */
    public function setUtmTags($utmTags)
    {
        $this->isChanged('utmTags', $utmTags);
        $this->utmTags = $utmTags;

        return $this;
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
        $this->isChanged('url', $url);
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getReadCount()
    {
        return $this->readCount;
    }

    /**
     * @param $readCount
     *
     * @return $this
     */
    public function setReadCount($readCount)
    {
        $this->readCount = $readCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @param $publishDown
     *
     * @return $this
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param $publishUp
     *
     * @return $this
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
     * @param $sentCount
     *
     * @return $this
     */
    public function setSentCount($sentCount)
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Add list.
     *
     * @param LeadList $list
     *
     * @return Notification
     */
    public function addList(LeadList $list)
    {
        $this->lists[] = $list;

        return $this;
    }

    /**
     * Remove list.
     *
     * @param LeadList $list
     */
    public function removeList(LeadList $list)
    {
        $this->lists->removeElement($list);
    }

    /**
     * @return mixed
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return string
     */
    public function getNotificationType()
    {
        return $this->notificationType;
    }

    /**
     * @param string $notificationType
     */
    public function setNotificationType($notificationType)
    {
        $this->isChanged('notificationType', $notificationType);
        $this->notificationType = $notificationType;
    }

    /**
     * @return bool
     */
    public function isMobile()
    {
        return $this->mobile;
    }

    /**
     * @param bool $mobile
     *
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * @return array
     */
    public function getMobileSettings()
    {
        return $this->mobileSettings;
    }

    /**
     * @param array $mobileSettings
     *
     * @return $this
     */
    public function setMobileSettings(array $mobileSettings)
    {
        $this->mobileSettings = $mobileSettings;

        return $this;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->isChanged('ttl', $ttl);
        $this->ttl = $ttl;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->isChanged('priority', $priority);
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getActionButtonIcon1()
    {
        return $this->actionButtonIcon1;
    }

    /**
     * @param string $actionButtonIcon1
     */
    public function setActionButtonIcon1($actionButtonIcon1)
    {
        $this->isChanged('actionButtonIcon1', $actionButtonIcon1);
        $this->actionButtonIcon1 = $actionButtonIcon1;
    }

    /**
     * @return string
     */
    public function getActionButtonUrl1()
    {
        return $this->actionButtonUrl1;
    }

    /**
     * @param string $actionButtonUrl1
     */
    public function setActionButtonUrl1($actionButtonUrl1)
    {
        $this->isChanged('actionButtonUrl1', $actionButtonUrl1);
        $this->actionButtonUrl1 = $actionButtonUrl1;
    }

    /**
     * @return string
     */
    public function getActionButtonText2()
    {
        return $this->actionButtonText2;
    }

    /**
     * @param string $actionButtonText2
     */
    public function setActionButtonText2($actionButtonText2)
    {
        $this->isChanged('actionButtonText2', $actionButtonText2);
        $this->actionButtonText2 = $actionButtonText2;
    }

    /**
     * @return string
     */
    public function getActionButtonIcon2()
    {
        return $this->actionButtonIcon2;
    }

    /**
     * @param string $actionButtonIcon2
     */
    public function setActionButtonIcon2($actionButtonIcon2)
    {
        $this->isChanged('actionButtonIcon2', $actionButtonIcon2);
        $this->actionButtonIcon2 = $actionButtonIcon2;
    }

    /**
     * @return string
     */
    public function getActionButtonUrl2()
    {
        return $this->actionButtonUrl2;
    }

    /**
     * @param string $actionButtonUrl2
     */
    public function setActionButtonUrl2($actionButtonUrl2)
    {
        $this->isChanged('actionButtonUrl2', $actionButtonUrl2);
        $this->actionButtonUrl2 = $actionButtonUrl2;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->isChanged('icon', $icon);
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->isChanged('image', $image);
        $this->image = $image;
    }
}
