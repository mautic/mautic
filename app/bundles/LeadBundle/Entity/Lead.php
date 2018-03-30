<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\NotificationBundle\Entity\PushID;
use Mautic\StageBundle\Entity\Stage;
use Mautic\UserBundle\Entity\User;

/**
 * Class Lead.
 */
class Lead extends FormEntity implements CustomFieldEntityInterface
{
    use CustomFieldEntityTrait;

    const FIELD_ALIAS     = '';
    const POINTS_ADD      = 'plus';
    const POINTS_SUBTRACT = 'minus';
    const POINTS_MULTIPLY = 'times';
    const POINTS_DIVIDE   = 'divide';

    /**
     * Used to determine social identity.
     *
     * @var array
     */
    private $availableSocialFields = [];

    /**
     * @var int
     */
    private $id;

    /**
     * @var
     */
    private $title;

    /**
     * @var
     */
    private $firstname;

    /**
     * @var
     */
    private $lastname;

    /**
     * @var
     */
    private $company;

    /**
     * @var
     */
    private $position;

    /**
     * @var
     */
    private $email;

    /**
     * @var
     */
    private $phone;

    /**
     * @var
     */
    private $mobile;

    /**
     * @var
     */
    private $address1;

    /**
     * @var
     */
    private $address2;

    /**
     * @var
     */
    private $city;

    /**
     * @var
     */
    private $state;

    /**
     * @var
     */
    private $zipcode;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @var
     */
    private $country;

    /**
     * @var \Mautic\UserBundle\Entity\User
     */
    private $owner;

    /**
     * @var int
     */
    private $points = 0;

    /**
     * @var array
     */
    private $pointChanges = [];

    /**
     * @var int|null
     */
    private $updatedPoints = null;

    /**
     * @var ArrayCollection
     */
    private $pointsChangeLog;

    /**
     * @var null
     */
    private $actualPoints = null;

    /**
     * @var ArrayCollection
     */
    private $companyChangeLog;

    /**
     * @var ArrayCollection
     */
    private $doNotContact;

    /**
     * @var ArrayCollection
     */
    private $ipAddresses;

    /**
     * @var ArrayCollection
     */
    private $pushIds;

    /**
     * @var ArrayCollection
     */
    private $eventLog;

    /**
     * @var \DateTime
     */
    private $lastActive;

    /**
     * @var array
     */
    private $internal = [];

    /**
     * @var array
     */
    private $socialCache = [];

    /**
     * Used to populate trigger color.
     *
     * @var string
     */
    private $color;

    /**
     * @var LeadManipulator
     */
    private $manipulator = null;

    /**
     * Sets if the IP was just created by LeadModel::getCurrentLead().
     *
     * @var bool
     */
    private $newlyCreated = false;

    /**
     * @var \DateTime
     */
    private $dateIdentified;

    /**
     * @var ArrayCollection
     */
    private $notes;

    /**
     * @var string
     */
    private $preferredProfileImage = 'gravatar';

    /**
     * @var bool
     */
    public $imported = false;

    /**
     * @var ArrayCollection
     */
    private $tags;

    /**
     * @var \Mautic\StageBundle\Entity\Stage
     */
    private $stage;

    /**
     * @var ArrayCollection
     */
    private $stageChangeLog;

    /**
     * @var ArrayCollection
     */
    private $utmtags;

    /**
     * @var \Mautic\LeadBundle\Entity\FrequencyRule[]
     */
    private $frequencyRules;

    /**
     * @var
     */
    private $primaryCompany;

    /**
     * Used to determine order of preferred channels.
     *
     * @var array
     */
    private $channelRules = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ipAddresses      = new ArrayCollection();
        $this->pushIds          = new ArrayCollection();
        $this->eventLog         = new ArrayCollection();
        $this->doNotContact     = new ArrayCollection();
        $this->pointsChangeLog  = new ArrayCollection();
        $this->tags             = new ArrayCollection();
        $this->stageChangeLog   = new ArrayCollection();
        $this->frequencyRules   = new ArrayCollection();
        $this->companyChangeLog = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('leads')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\LeadRepository')
            ->addLifecycleEvent('checkDateIdentified', 'preUpdate')
            ->addLifecycleEvent('checkDateIdentified', 'prePersist')
            ->addLifecycleEvent('checkAttributionDate', 'preUpdate')
            ->addLifecycleEvent('checkAttributionDate', 'prePersist')
            ->addIndex(['date_added'], 'lead_date_added');

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createManyToOne('owner', 'Mautic\UserBundle\Entity\User')
            ->fetchLazy()
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createField('points', 'integer')
            ->build();

        $builder->createOneToMany('pointsChangeLog', 'PointsChangeLog')
            ->orphanRemoval()
            ->setOrderBy(['dateAdded' => 'DESC'])
            ->mappedBy('lead')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('companyChangeLog', 'CompanyChangeLog')
            ->orphanRemoval()
            ->setOrderBy(['dateAdded' => 'DESC'])
            ->mappedBy('lead')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('doNotContact', 'Mautic\LeadBundle\Entity\DoNotContact')
            ->orphanRemoval()
            ->mappedBy('lead')
            ->cascadePersist()
            ->cascadeDetach()
            ->cascadeMerge()
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToMany('ipAddresses', 'Mautic\CoreBundle\Entity\IpAddress')
            ->setJoinTable('lead_ips_xref')
            ->addInverseJoinColumn('ip_id', 'id', false)
            ->addJoinColumn('lead_id', 'id', false, false, 'CASCADE')
            ->setIndexBy('ipAddress')
            ->cascadeMerge()
            ->cascadePersist()
            ->cascadeDetach()
            ->build();

        $builder->createOneToMany('pushIds', 'Mautic\NotificationBundle\Entity\PushID')
            ->orphanRemoval()
            ->mappedBy('lead')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('eventLog', LeadEventLog::class)
            ->mappedBy('lead')
            ->cascadePersist()
            ->cascadeMerge()
            ->cascadeDetach()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('lastActive', 'datetime')
            ->columnName('last_active')
            ->nullable()
            ->build();

        $builder->createField('internal', 'array')
            ->nullable()
            ->build();

        $builder->createField('socialCache', 'array')
            ->columnName('social_cache')
            ->nullable()
            ->build();

        $builder->createField('dateIdentified', 'datetime')
            ->columnName('date_identified')
            ->nullable()
            ->build();

        $builder->createOneToMany('notes', 'LeadNote')
            ->orphanRemoval()
            ->setOrderBy(['dateAdded' => 'DESC'])
            ->mappedBy('lead')
            ->cascadeDetach()
            ->cascadeMerge()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('preferredProfileImage', 'string')
            ->columnName('preferred_profile_image')
            ->nullable()
            ->build();

        $builder->createManyToMany('tags', 'Mautic\LeadBundle\Entity\Tag')
            ->setJoinTable('lead_tags_xref')
            ->addInverseJoinColumn('tag_id', 'id', false)
            ->addJoinColumn('lead_id', 'id', false, false, 'CASCADE')
            ->setOrderBy(['tag' => 'ASC'])
            ->setIndexBy('tag')
            ->fetchLazy()
            ->cascadeMerge()
            ->cascadePersist()
            ->cascadeDetach()
            ->build();

        $builder->createManyToOne('stage', 'Mautic\StageBundle\Entity\Stage')
            ->cascadePersist()
            ->cascadeMerge()
            ->cascadeDetach()
            ->addJoinColumn('stage_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createOneToMany('stageChangeLog', 'StagesChangeLog')
            ->orphanRemoval()
            ->setOrderBy(['dateAdded' => 'DESC'])
            ->mappedBy('lead')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('utmtags', 'Mautic\LeadBundle\Entity\UtmTag')
            ->orphanRemoval()
            ->mappedBy('lead')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('frequencyRules', 'Mautic\LeadBundle\Entity\FrequencyRule')
            ->orphanRemoval()
            ->setIndexBy('channel')
            ->setOrderBy(['dateAdded' => 'DESC'])
            ->mappedBy('lead')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        self::loadFixedFieldMetadata(
            $builder,
            [
                'title',
                'firstname',
                'lastname',
                'company',
                'position',
                'email',
                'phone',
                'mobile',
                'address1',
                'address2',
                'city',
                'state',
                'zipcode',
                'timezone',
                'country',
            ],
            FieldModel::$coreFields
        );
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setRoot('lead')
            ->setGroupPrefix('leadBasic')
            ->addListProperties(
                [
                    'id',
                    'points',
                    'color',
                    'title',
                    'firstname',
                    'lastname',
                    'company',
                    'position',
                    'email',
                    'phone',
                    'mobile',
                    'address1',
                    'address2',
                    'city',
                    'state',
                    'zipcode',
                    'timezone',
                    'country',
                ]
            )
            ->setGroupPrefix('lead')
            ->addListProperties(
                [
                    'id',
                    'points',
                    'color',
                    'fields',
                ]
            )
            ->addProperties(
                [
                    'lastActive',
                    'owner',
                    'ipAddresses',
                    'tags',
                    'utmtags',
                    'stage',
                    'dateIdentified',
                    'preferredProfileImage',
                    'doNotContact',
                    'frequencyRules',
                ]
            )
            ->build();
    }

    /**
     * @param string $prop
     * @param mixed  $val
     * @param null   $oldValue
     */
    protected function isChanged($prop, $val, $oldValue = null)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $oldValue !== null ? $oldValue : $this->$getter();
        if ($prop == 'owner') {
            if ($current && !$val) {
                $this->changes['owner'] = [$current->getId(), $val];
            } elseif (!$current && $val) {
                $this->changes['owner'] = [$current, $val->getId()];
            } elseif ($current && $val && $current->getId() != $val->getId()) {
                $this->changes['owner'] = [$current->getId(), $val->getId()];
            }
        } elseif ($prop == 'ipAddresses') {
            $this->changes['ipAddresses'] = ['', $val->getIpAddress()];
        } elseif ($prop == 'tags') {
            if ($val instanceof Tag) {
                $this->changes['tags']['added'][] = $val->getTag();
            } else {
                $this->changes['tags']['removed'][] = $val;
            }
        } elseif ($prop == 'utmtags') {
            if ($val instanceof UtmTag) {
                if ($val->getUtmContent()) {
                    $this->changes['utmtags'] = ['utm_content', $val->getUtmContent()];
                }
                if ($val->getUtmMedium()) {
                    $this->changes['utmtags'] = ['utm_medium', $val->getUtmMedium()];
                }
                if ($val->getUtmCampaign()) {
                    $this->changes['utmtags'] = ['utm_campaign', $val->getUtmCampaign()];
                }
                if ($val->getUtmTerm()) {
                    $this->changes['utmtags'] = ['utm_term', $val->getUtmTerm()];
                }
                if ($val->getUtmSource()) {
                    $this->changes['utmtags'] = ['utm_source', $val->getUtmSource()];
                }
            }
        } elseif ($prop == 'frequencyRules') {
            if (!isset($this->changes['frequencyRules'])) {
                $this->changes['frequencyRules'] = [];
            }

            if ($val instanceof FrequencyRule) {
                $channel = $val->getChannel();

                $this->changes['frequencyRules'][$channel] = $val->getChanges();
            } else {
                $this->changes['frequencyRules']['removed'][] = $val;
            }
        } elseif ($prop == 'stage') {
            if ($current && !$val) {
                $this->changes['stage'] = [$current->getId(), $val];
            } elseif (!$current && $val) {
                $this->changes['stage'] = [$current, $val->getId()];
            } elseif ($current && $val && $current->getId() != $val->getId()) {
                $this->changes['stage'] = [$current->getId(), $val->getId()];
            }
        } elseif ($prop == 'points' && $current != $val) {
            $this->changes['points'] = [$current, $val];
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * @return array
     */
    public function convertToArray()
    {
        return get_object_vars($this);
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Lead
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set owner.
     *
     * @param User $owner
     *
     * @return Lead
     */
    public function setOwner(User $owner = null)
    {
        $this->isChanged('owner', $owner);
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner.
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Returns the user to be used for permissions.
     *
     * @return User|int
     */
    public function getPermissionUser()
    {
        return (null === $this->getOwner()) ? $this->getCreatedBy() : $this->getOwner();
    }

    /**
     * Add ipAddress.
     *
     * @param IpAddress $ipAddress
     *
     * @return Lead
     */
    public function addIpAddress(IpAddress $ipAddress)
    {
        if (!$ipAddress->isTrackable()) {
            return $this;
        }

        $ip = $ipAddress->getIpAddress();
        if (!isset($this->ipAddresses[$ip])) {
            $this->isChanged('ipAddresses', $ipAddress);
            $this->ipAddresses[$ip] = $ipAddress;
        }

        return $this;
    }

    /**
     * Remove ipAddress.
     *
     * @param IpAddress $ipAddress
     */
    public function removeIpAddress(IpAddress $ipAddress)
    {
        $this->ipAddresses->removeElement($ipAddress);
    }

    /**
     * Get ipAddresses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIpAddresses()
    {
        return $this->ipAddresses;
    }

    /**
     * Get full name.
     *
     * @param bool $lastFirst
     *
     * @return string
     */
    public function getName($lastFirst = false)
    {
        $firstName = $this->getFirstname();
        $lastName  = $this->getLastname();

        $fullName = '';
        if ($lastFirst && $firstName && $lastName) {
            $fullName = $lastName.', '.$firstName;
        } elseif ($firstName && $lastName) {
            $fullName = $firstName.' '.$lastName;
        } elseif ($firstName) {
            $fullName = $firstName;
        } elseif ($lastName) {
            $fullName = $lastName;
        }

        return $fullName;
    }

    /**
     * Get preferred locale.
     *
     * @return string
     */
    public function getPreferredLocale()
    {
        if (isset($this->updatedFields['preferred_locale'])) {
            return $this->updatedFields['preferred_locale'];
        }

        if (!empty($this->fields['core']['preferred_locale']['value'])) {
            return $this->fields['core']['preferred_locale']['value'];
        }

        return '';
    }

    /**
     * Get the primary identifier for the lead.
     *
     * @param bool $lastFirst
     *
     * @return string
     */
    public function getPrimaryIdentifier($lastFirst = false)
    {
        if ($name = $this->getName($lastFirst)) {
            return $name;
        } elseif ($this->getCompany()) {
            return $this->getCompany();
        } elseif ($this->getEmail()) {
            return $this->getEmail();
        } elseif ($socialIdentity = $this->getFirstSocialIdentity()) {
            return $socialIdentity;
        } elseif (count($ips = $this->getIpAddresses())) {
            return $ips->first()->getIpAddress();
        } else {
            return 'mautic.lead.lead.anonymous';
        }
    }

    /**
     * Get the secondary identifier for the lead; mainly company.
     *
     * @return string
     */
    public function getSecondaryIdentifier()
    {
        if (!$this->getCompany()) {
            return $this->getCompany();
        }

        return '';
    }

    /**
     * Get the location for the lead.
     *
     * @return string
     */
    public function getLocation()
    {
        $location = '';

        if ($this->getCity()) {
            $location .= $this->getCity().', ';
        }

        if ($this->getState()) {
            $location .= $this->getState().', ';
        }

        if ($this->getCountry()) {
            $location .= $this->getCountry().', ';
        }

        return rtrim($location, ', ');
    }

    /**
     * Point changes are tracked and will be persisted as a direct DB query to avoid PHP memory overwrites with concurrent requests
     * The risk in this is that the $changes['points'] may not be accurate but at least no points are lost.
     *
     * @param int    $points
     * @param string $operator
     *
     * @return Lead
     */
    public function adjustPoints($points, $operator = self::POINTS_ADD)
    {
        if (!$points = (int) $points) {
            return $this;
        }

        // Use $updatedPoints in an attempt to keep track in the $changes log although this may not be accurate if the DB updates the points rather
        // than PHP memory
        if (null == $this->updatedPoints) {
            $this->updatedPoints = $this->points;
        }
        $oldPoints = $this->updatedPoints;

        switch ($operator) {
            case self::POINTS_ADD:
                $this->updatedPoints += $points;
                $operator = '+';
                break;
            case self::POINTS_SUBTRACT:
                $this->updatedPoints -= $points;
                $operator = '-';
                break;
            case self::POINTS_MULTIPLY:
                $this->updatedPoints *= $points;
                $operator = '*';
                break;
            case self::POINTS_DIVIDE:
                $this->updatedPoints /= $points;
                $operator = '/';
                break;
            default:
                throw new \UnexpectedValueException('Invalid operator');
        }

        // Keep track of point changes to make a direct DB query
        // Ignoring Aunt Sally here (PEMDAS)
        if (!isset($this->pointChanges[$operator])) {
            $this->pointChanges[$operator] = 0;
        }
        $this->pointChanges[$operator] += $points;

        $this->isChanged('points', (int) $this->updatedPoints, (int) $oldPoints);

        return $this;
    }

    /**
     * @return array
     */
    public function getPointChanges()
    {
        return $this->pointChanges;
    }

    /**
     * Set points.
     *
     * @param int $points
     *
     * @return Lead
     */
    public function setPoints($points)
    {
        $this->isChanged('points', $points);
        $this->points = (int) $points;

        // Something is setting points directly so reset points updated by database
        $this->resetPointChanges();

        return $this;
    }

    /**
     * Get points.
     *
     * @return int
     */
    public function getPoints()
    {
        if (null !== $this->actualPoints) {
            return $this->actualPoints;
        } elseif (null !== $this->updatedPoints) {
            return $this->updatedPoints;
        }

        return $this->points;
    }

    /**
     * Set by the repository method when points are updated and requeried directly on the DB side.
     *
     * @param $points
     */
    public function setActualPoints($points)
    {
        $this->actualPoints = (int) $points;
        $this->pointChanges = [];
    }

    /**
     * Reset point changes.
     *
     * @return $this
     */
    public function resetPointChanges()
    {
        $this->actualPoints  = null;
        $this->pointChanges  = [];
        $this->updatedPoints = null;

        return $this;
    }

    /**
     * Creates a points change entry.
     *
     * @param           $type
     * @param           $name
     * @param           $action
     * @param           $pointChanges
     * @param IpAddress $ip
     */
    public function addPointsChangeLogEntry($type, $name, $action, $pointChanges, IpAddress $ip)
    {
        if ($pointChanges === 0) {
            // No need to record no change
            return;
        }

        // Create a new points change event
        $event = new PointsChangeLog();
        $event->setType($type);
        $event->setEventName($name);
        $event->setActionName($action);
        $event->setDateAdded(new \DateTime());
        $event->setDelta($pointChanges);
        $event->setIpAddress($ip);
        $event->setLead($this);
        $this->addPointsChangeLog($event);
    }

    /**
     * Add pointsChangeLog.
     *
     * @param PointsChangeLog $pointsChangeLog
     *
     * @return Lead
     */
    public function addPointsChangeLog(PointsChangeLog $pointsChangeLog)
    {
        $this->pointsChangeLog[] = $pointsChangeLog;

        return $this;
    }

    /**
     * Creates a points change entry.
     *
     * @param $stage
     * @param $name
     * @param $action
     */
    public function stageChangeLogEntry($stage, $name, $action)
    {
        //create a new points change event
        $event = new StagesChangeLog();
        $event->setStage($stage);
        $event->setEventName($name);
        $event->setActionName($action);
        $event->setDateAdded(new \DateTime());
        $event->setLead($this);
        $this->stageChangeLog($event);
    }

    /**
     * Add StagesChangeLog.
     *
     * @param StagesChangeLog $stageChangeLog
     *
     * @return Lead
     */
    public function stageChangeLog(StagesChangeLog $stageChangeLog)
    {
        $this->stageChangeLog[] = $stageChangeLog;

        return $this;
    }

    /**
     * Remove pointsChangeLog.
     *
     * @param PointsChangeLog $pointsChangeLog
     */
    public function removePointsChangeLog(PointsChangeLog $pointsChangeLog)
    {
        $this->pointsChangeLog->removeElement($pointsChangeLog);
    }

    /**
     * Get pointsChangeLog.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPointsChangeLog()
    {
        return $this->pointsChangeLog;
    }

    /**
     * @param      $type
     * @param      $name
     * @param      $action
     * @param null $company
     */
    public function addCompanyChangeLogEntry($type, $name, $action, $company = null)
    {
        if (!$company) {
            // No need to record a null delta
            return;
        }

        // Create a new company change event
        $event = new CompanyChangeLog();
        $event->setType($type);
        $event->setEventName($name);
        $event->setActionName($action);
        $event->setDateAdded(new \DateTime());
        $event->setCompany($company);
        $event->setLead($this);
        $this->addCompanyChangeLog($event);
    }

    /**
     * Add Company ChangeLog.
     *
     * @param CompanyChangeLog $companyChangeLog
     *
     * @return Lead
     */
    public function addCompanyChangeLog(CompanyChangeLog $companyChangeLog)
    {
        $this->companyChangeLog[] = $companyChangeLog;

        return $this;
    }

    /**
     * @param      $identifier
     * @param bool $enabled
     * @param bool $mobile
     *
     * @return $this
     */
    public function addPushIDEntry($identifier, $enabled = true, $mobile = false)
    {
        $entity = new PushID();

        /** @var PushID $id */
        foreach ($this->pushIds as $id) {
            if ($id->getPushID() === $identifier) {
                if ($id->isEnabled() === $enabled) {
                    return $this;
                } else {
                    $entity = $id;
                    $this->removePushID($id);
                }
            }
        }

        $entity->setPushID($identifier);
        $entity->setLead($this);
        $entity->setEnabled($enabled);
        $entity->setMobile($mobile);

        $this->addPushID($entity);

        $this->isChanged('pushIds', $this->pushIds);

        return $this;
    }

    /**
     * @param PushID $pushID
     *
     * @return $this
     */
    public function addPushID(PushID $pushID)
    {
        $this->pushIds[] = $pushID;

        return $this;
    }

    /**
     * @param PushID $pushID
     */
    public function removePushID(PushID $pushID)
    {
        $this->pushIds->removeElement($pushID);
    }

    /**
     * @return ArrayCollection
     */
    public function getPushIDs()
    {
        return $this->pushIds;
    }

    /**
     * @param LeadEventLog $eventLog
     *
     * @return $this
     */
    public function addEventLog(LeadEventLog $log)
    {
        $this->eventLog[] = $log;
        $log->setLead($this);

        return $this;
    }

    /**
     * @param LeadEventLog $eventLog
     */
    public function removeEventLog(LeadEventLog $eventLog)
    {
        $this->eventLog->removeElement($eventLog);
    }

    /**
     * @param DoNotContact $doNotContact
     *
     * @return $this
     */
    public function addDoNotContactEntry(DoNotContact $doNotContact)
    {
        $this->changes['dnc_channel_status'][$doNotContact->getChannel()] = [
            'reason'   => $doNotContact->getReason(),
            'comments' => $doNotContact->getComments(),
        ];

        // @deprecated - to be removed in 2.0
        switch ($doNotContact->getReason()) {
            case DoNotContact::BOUNCED:
                $type = 'bounced';
                break;
            case DoNotContact::MANUAL:
                $type = 'manual';
                break;
            case DoNotContact::UNSUBSCRIBED:
            default:
                $type = 'unsubscribed';
                break;
        }
        $this->changes['dnc_status'] = [$type, $doNotContact->getComments()];

        $this->doNotContact[$doNotContact->getChannel()] = $doNotContact;

        return $this;
    }

    /**
     * @param DoNotContact $doNotContact
     */
    public function removeDoNotContactEntry(DoNotContact $doNotContact)
    {
        $this->changes['dnc_channel_status'][$doNotContact->getChannel()] = [
            'reason'     => DoNotContact::IS_CONTACTABLE,
            'old_reason' => $doNotContact->getReason(),
            'comments'   => $doNotContact->getComments(),
        ];

        // @deprecated to be removed in 2.0
        $this->changes['dnc_status'] = ['removed', $doNotContact->getComments()];

        $this->doNotContact->removeElement($doNotContact);
    }

    /**
     * @return ArrayCollection
     */
    public function getDoNotContact()
    {
        return $this->doNotContact;
    }

    /**
     * Set internal storage.
     *
     * @param $internal
     */
    public function setInternal($internal)
    {
        $this->internal = $internal;
    }

    /**
     * Get internal storage.
     *
     * @return mixed
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * Set social cache.
     *
     * @param $cache
     */
    public function setSocialCache($cache)
    {
        $this->socialCache = $cache;
    }

    /**
     * Get social cache.
     *
     * @return mixed
     */
    public function getSocialCache()
    {
        return $this->socialCache;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return bool
     */
    public function isAnonymous()
    {
        return !($this->getName()
            || $this->getFirstname()
            || $this->getLastname()
            || $this->getCompany()
            || $this->getEmail()
            || $this->getFirstSocialIdentity()
        );
    }

    /**
     * @return bool
     */
    public function wasAnonymous()
    {
        return $this->dateIdentified == null && $this->isAnonymous() === false;
    }

    /**
     * @return bool
     */
    protected function getFirstSocialIdentity()
    {
        if (isset($this->fields['social'])) {
            foreach ($this->fields['social'] as $social) {
                if (!empty($social['value'])) {
                    return $social['value'];
                }
            }
        } elseif (!empty($this->updatedFields)) {
            foreach ($this->availableSocialFields as $social) {
                if (!empty($this->updatedFields[$social])) {
                    return $this->updatedFields[$social];
                }
            }
        }

        return false;
    }

    /**
     * @param LeadManipulator|null $manipulator
     *
     * @return self
     */
    public function setManipulator(LeadManipulator $manipulator = null)
    {
        $this->manipulator = $manipulator;

        return $this;
    }

    /**
     * @return LeadManipulator|null
     */
    public function getManipulator()
    {
        return $this->manipulator;
    }

    /**
     * @return bool
     */
    public function isNewlyCreated()
    {
        return $this->newlyCreated;
    }

    /**
     * @param bool $newlyCreated Created
     */
    public function setNewlyCreated($newlyCreated)
    {
        $this->newlyCreated = $newlyCreated;
    }

    /**
     * @return mixed
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $source
     */
    public function setPreferredProfileImage($source)
    {
        $this->preferredProfileImage = $source;
    }

    /**
     * @return string
     */
    public function getPreferredProfileImage()
    {
        return $this->preferredProfileImage;
    }

    /**
     * @return mixed
     */
    public function getDateIdentified()
    {
        return $this->dateIdentified;
    }

    /**
     * @param mixed $dateIdentified
     */
    public function setDateIdentified($dateIdentified)
    {
        $this->isChanged('dateIdentified', $dateIdentified);
        $this->dateIdentified = $dateIdentified;
    }

    /**
     * @return mixed
     */
    public function getLastActive()
    {
        return $this->lastActive;
    }

    /**
     * @param mixed $lastActive
     */
    public function setLastActive($lastActive)
    {
        $this->changes['dateLastActive'] = [$this->lastActive, $lastActive];
        $this->lastActive                = $lastActive;
    }

    /**
     * @param array $availableSocialFields
     */
    public function setAvailableSocialFields(array $availableSocialFields)
    {
        $this->availableSocialFields = $availableSocialFields;
    }

    /**
     * Add tag.
     *
     * @param Tag $tag
     *
     * @return Lead
     */
    public function addTag(Tag $tag)
    {
        $this->isChanged('tags', $tag);
        $this->tags[$tag->getTag()] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->isChanged('tags', $tag->getTag());
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags.
     *
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set tags.
     *
     * @param $tags
     *
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get utm tags.
     *
     * @return mixed
     */
    public function getUtmTags()
    {
        return $this->utmtags;
    }

    /**
     * Set utm tags.
     *
     * @param $utmTags
     *
     * @return $this
     */
    public function setUtmTags($utmTags)
    {
        $this->isChanged('utmtags', $utmTags);
        $this->utmtags[] = $utmTags;

        return $this;
    }

    /**
     * @param UtmTag $utmTag
     */
    public function removeUtmTagEntry(UtmTag $utmTag)
    {
        $this->changes['utmtags'] = ['removed', 'UtmTagID:'.$utmTag->getId()];
        $this->utmtags->removeElement($utmTag);
    }

    /**
     * Set stage.
     *
     * @param \Mautic\StageBundle\Entity\Stage $stage
     *
     * @return Stage
     */
    public function setStage(Stage $stage = null)
    {
        $this->isChanged('stage', $stage);
        $this->stage = $stage;

        return $this;
    }

    /**
     * Get stage.
     *
     * @return \Mautic\StageBundle\Entity\Stage
     */
    public function getStage()
    {
        return $this->stage;
    }

    /**
     * Set frequency rules.
     *
     * @param FrequencyRule[] $frequencyRules
     *
     * @return Lead
     */
    public function setFrequencyRules($frequencyRules)
    {
        $this->frequencyRules = $frequencyRules;

        return $this;
    }

    /**
     * Get frequency rules.
     *
     * @return ArrayCollection
     */
    public function getFrequencyRules()
    {
        return $this->frequencyRules;
    }

    /**
     * Remove frequencyRule.
     *
     * @param FrequencyRule $frequencyRule
     */
    public function removeFrequencyRule(FrequencyRule $frequencyRule)
    {
        $this->isChanged('frequencyRules', $frequencyRule->getId(), false);
        $this->frequencyRules->removeElement($frequencyRule);
    }

    /**
     * Add frequency rule.
     *
     * @param FrequencyRule $frequencyRule
     */
    public function addFrequencyRule(FrequencyRule $frequencyRule)
    {
        $this->isChanged('frequencyRules', $frequencyRule, false);
        $this->frequencyRules[] = $frequencyRule;
    }

    /**
     * Get attribution value.
     *
     * @return bool
     */
    public function getAttribution()
    {
        return (float) $this->getFieldValue('attribution');
    }

    /**
     * If there is an attribution amount but no date, insert today's date.
     */
    public function checkAttributionDate()
    {
        $attribution     = $this->getFieldValue('attribution');
        $attributionDate = $this->getFieldValue('attribution_date');

        if (!empty($attribution) && empty($attributionDate)) {
            $this->addUpdatedField('attribution_date', (new \DateTime())->format('Y-m-d'));
        } elseif (empty($attribution) && !empty($attributionDate)) {
            $this->addUpdatedField('attribution_date', null);
        }
    }

    /**
     * Set date identified.
     */
    public function checkDateIdentified()
    {
        if ($this->wasAnonymous()) {
            $this->dateIdentified            = new \DateTime();
            $this->changes['dateIdentified'] = ['', $this->dateIdentified];
        }
    }

    /**
     * @return mixed
     */
    public function getPrimaryCompany()
    {
        return $this->primaryCompany;
    }

    /**
     * @param mixed $primaryCompany
     *
     * @return Lead
     */
    public function setPrimaryCompany($primaryCompany)
    {
        $this->primaryCompany = $primaryCompany;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     *
     * @return Lead
     */
    public function setTitle($title)
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     *
     * @return Lead
     */
    public function setFirstname($firstname)
    {
        $this->isChanged('firstname', $firstname);
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     *
     * @return Lead
     */
    public function setLastname($lastname)
    {
        $this->isChanged('lastname', $lastname);
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     *
     * @return Lead
     */
    public function setPosition($position)
    {
        $this->isChanged('position', $position);
        $this->position = $position;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     *
     * @return Lead
     */
    public function setPhone($phone)
    {
        $this->isChanged('phone', $phone);
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param mixed $mobile
     *
     * @return Lead
     */
    public function setMobile($mobile)
    {
        $this->isChanged('mobile', $mobile);
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param mixed $address1
     *
     * @return Lead
     */
    public function setAddress1($address1)
    {
        $this->isChanged('address1', $address1);
        $this->address1 = $address1;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param mixed $address2
     *
     * @return Lead
     */
    public function setAddress2($address2)
    {
        $this->isChanged('address2', $address2);
        $this->address2 = $address2;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     *
     * @return Lead
     */
    public function setCity($city)
    {
        $this->isChanged('city', $city);
        $this->city = $city;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     *
     * @return Lead
     */
    public function setState($state)
    {
        $this->isChanged('state', $state);
        $this->state = $state;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @param mixed $zipcode
     *
     * @return Lead
     */
    public function setZipcode($zipcode)
    {
        $this->isChanged('zipcode', $zipcode);
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     *
     * @return Lead
     */
    public function setTimezone($timezone)
    {
        $this->isChanged('timezone', $timezone);
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     *
     * @return Lead
     */
    public function setCountry($country)
    {
        $this->isChanged('country', $country);
        $this->country = $country;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     *
     * @return Lead
     */
    public function setCompany($company)
    {
        $this->isChanged('company', $company);
        $this->company = $company;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     *
     * @return Lead
     */
    public function setEmail($email)
    {
        $this->isChanged('email', $email);
        $this->email = $email;

        return $this;
    }

    /**
     * Returns array of rules with preferred channels first.
     *
     * @return mixed
     */
    public function getChannelRules()
    {
        if (null === $this->channelRules) {
            $frequencyRules = $this->getFrequencyRules()->toArray();
            $dnc            = $this->getDoNotContact();
            $dncChannels    = [];
            /** @var DoNotContact $record */
            foreach ($dnc as $record) {
                $dncChannels[$record->getChannel()] = $record->getReason();
            }

            $this->channelRules = self::generateChannelRules($frequencyRules, $dncChannels);
        }

        return $this->channelRules;
    }

    /**
     * @param array $rules
     *
     * @return $this
     */
    public function setChannelRules(array $rules)
    {
        $this->channelRules = $rules;

        return $this;
    }

    /**
     * Used mostly when batching to generate preferred channels without hydrating associations one at a time.
     *
     * @param array $frequencyRules
     * @param array $dncRules
     */
    public static function generateChannelRules(array $frequencyRules, array $dncRules)
    {
        $rules             = [];
        $dncFrequencyRules = [];
        foreach ($frequencyRules as $rule) {
            if ($rule instanceof FrequencyRule) {
                $ruleArray = [
                    'channel'           => $rule->getChannel(),
                    'pause_from_date'   => $rule->getPauseFromDate(),
                    'pause_to_date'     => $rule->getPauseToDate(),
                    'preferred_channel' => $rule->getPreferredChannel(),
                    'frequency_time'    => $rule->getFrequencyTime(),
                    'frequency_number'  => $rule->getFrequencyNumber(),
                ];

                if (array_key_exists($rule->getChannel(), $dncRules)) {
                    $dncFrequencyRules[$rule->getChannel()] = $ruleArray;
                } else {
                    $rules[$rule->getChannel()] = $ruleArray;
                }
            } else {
                // Already an array
                break;
            }
        }

        if (count($rules)) {
            $frequencyRules = $rules;
        }

        /* @var FrequencyRule $rule */
        usort(
            $frequencyRules,
            function ($a, $b) {
                if ($a['pause_from_date'] && $a['pause_to_date']) {
                    $now = new \DateTime();
                    if ($now >= $a['pause_from_date'] && $now <= $a['pause_to_date']) {
                        // A is paused so give lower preference
                        return 1;
                    }
                }

                if ($a['preferred_channel'] === $b['preferred_channel']) {
                    if (!$a['frequency_time'] || !$b['frequency_time'] || !$a['frequency_number'] || !$b['frequency_number']) {
                        return 0;
                    }

                    // Order by which ever can be sent more frequent
                    if ($a['frequency_time'] === $b['frequency_time']) {
                        if ($a['frequency_number'] === $b['frequency_number']) {
                            return 0;
                        }

                        return ($a['frequency_number'] > $b['frequency_number']) ? -1 : 1;
                    } else {
                        $convertToMonth = function ($number, $unit) {
                            switch ($unit) {
                                case FrequencyRule::TIME_MONTH:
                                    $number = (int) $number;
                                    break;
                                case FrequencyRule::TIME_WEEK:
                                    $number = $number * 4;
                                    break;
                                case FrequencyRule::TIME_DAY:
                                    $number = $number * 30;
                                    break;
                            }

                            return $number;
                        };

                        $aFrequency = $convertToMonth($a['frequency_number'], $a['frequency_time']);
                        $bFrequency = $convertToMonth($b['frequency_number'], $b['frequency_time']);

                        if ($aFrequency === $bFrequency) {
                            return 0;
                        }

                        return ($aFrequency > $bFrequency) ? -1 : 1;
                    }
                }

                return ($a['preferred_channel'] > $b['preferred_channel']) ? -1 : 1;
            }
        );

        $rules = [];
        foreach ($frequencyRules as $rule) {
            $rules[$rule['channel']] =
                [
                    'frequency' => $rule,
                    'dnc'       => DoNotContact::IS_CONTACTABLE,
                ];
        }

        if (count($dncRules)) {
            foreach ($dncRules as $channel => $reason) {
                $rules[$channel] = [
                    'frequency' => (isset($dncFrequencyRules[$channel])) ? $dncFrequencyRules[$channel] : null,
                    'dnc'       => $reason,
                ];
            }
        }

        return $rules;
    }
}
