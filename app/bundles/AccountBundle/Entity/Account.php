<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AccountBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Account
 *
 * @package Mautic\AccountBundle\Entity
 */
class Account extends FormEntity
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $accountNumber;

    /**
     * @var string
     */
    private $accountSource;

    /**
     * @var float
     */
    private $annualRevenue;

    /**
     * @var string
     */
    private $address1;

    /**
     * @var string
     */
    private $address2;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $email;


    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $zipcode;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $industry;

    /**
     * @var ArrayCollection
     */
    private $leads;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $numberOfEmployees;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var string
     */
    private $fax;

    /**
     * @var integer
     */
    private $rating;

    /**
     * @var string
     */
    private $website;

    /**
     * @var int
     */
    private $type;

    /**
     * @var \Mautic\UserBundle\Entity\User
     */
    private $owner;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * Construct
     */
    public function __construct()
    {
        $this->leads = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('accounts')
            ->setCustomRepositoryClass('Mautic\AccountBundle\Entity\AccountRepository');

        $builder->addIdColumns();

        $builder->createField('accountNumber', 'string')
            ->columnName('account_number')
            ->nullable()
            ->build();

        $builder->createField('accountSource', 'string')
            ->columnName('account_source')
            ->nullable()
            ->build();

        $builder->addField('address1', 'string');

        $builder->addField('address2', 'string');

        $builder->createField('annualRevenue', 'float')
            ->columnName('annual_revenue')
            ->nullable()
            ->build();

        $builder->addField('city', 'string');

        $builder->addField('country', 'string');

        $builder->addField('description', 'text');

        $builder->addField('email', 'text');

        $builder->addField('fax', 'string');

        $builder->addField('industry', 'string');

        $builder->createManyToMany('leads', 'Mautic\LeadBundle\Entity\Lead')
            ->setJoinTable('account_leads_xref')
            ->addInverseJoinColumn('lead_id', 'id', false)
            ->addJoinColumn('account_id', 'id', false, false, 'CASCADE')
            ->setIndexBy('account')
            ->cascadeMerge()
            ->cascadePersist()
            ->cascadeDetach()
            ->build();

        $builder->createField('numberOfEmployees', 'integer')
            ->columnName('number_of_employees')
            ->nullable()
            ->build();

        $builder->createManyToOne('owner', 'Mautic\UserBundle\Entity\User')
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addField('phone', 'string');

        $builder->addPublishDates();

        $builder->addField('rating', 'integer');

        $builder->addField('state', 'string');

        $builder->addField('type', 'string');

        $builder->addField('website', 'string');

        $builder->addField('zipcode', 'string');

    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata (ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(array(
            'message' => 'mautic.core.name.required'
        )));
    }

    /**
     * Prepares the metadata for API usage
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('account')
            ->addListProperties(
                array(
                    'id',
                    'accountNumber',
                    'accountSource',
                    'address1',
                    'address2',
                    'annualRevenue',
                    'city',
                    'country',
                    'description',
                    'email',
                    'fax',
                    'industry',
                    'leads',
                    'name',
                    'numberOfEmployees',
                    'owner',
                    'phone',
                    'owner',
                    'rating',
                    'state',
                    'type',
                    'website',
                    'zipcode'
                )
            )
            ->build();
    }

    /**
     * @param string $prop
     * @param mixed  $val
     *
     * @return void
     */
    protected function isChanged ($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        $this->changes[$prop] = array($current, $val);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * Set accountNumber
     *
     * @param integer $accountNumber
     *
     * @return string
     */
    public function setAccountNumber ($accountNumber)
    {
        $this->isChanged('accountNumber', $accountNumber);
        $this->accountNumber = $accountNumber;

        return $this;
    }

    /**
     * Get accountNumber
     *
     * @return string
     */
    public function getAccountNumber ()
    {
        return $this->accountNumber;
    }

    /**
     * Get accountSource
     *
     * @return string
     */
    public function getAccountSource ()
    {
        return $this->accountSource;
    }

    /**
     * Set accountSource
     *
     * @param integer $accountSource
     *
     * @return string
     */
    public function setAccountSource ($accountSource)
    {
        $this->isChanged('accountSource', $accountSource);
        $this->accountSource = $accountSource;

        return $this;
    }

    /**
     * Get Address1
     *
     * @return string
     */
    public function getAddress1 ()
    {
        return $this->adress1;
    }

    /**
     * Set Address
     *
     * @param integer $address1
     *
     * @return string
     */
    public function setAddress1 ($address1)
    {
        $this->isChanged('address1', $address1);
        $this->address1 = $address1;

        return $this;
    }

    /**
     * Get Address2
     *
     * @return string
     */
    public function getAddress2 ()
    {
        return $this->adress2;
    }

    /**
     * Set Address
     *
     * @param integer $address2
     *
     * @return string
     */
    public function setAddress2 ($address2)
    {
        $this->isChanged('address2', $address2);
        $this->address2 = $address2;

        return $this;
    }

    /**
     * Get AnnualRevenue
     *
     * @return float
     */
    public function getAnnualRevenue ()
    {
        return $this->annualRevenue;
    }

    /**
     * Set AnnualRevenue
     *
     * @param integer $annualRevenue
     *
     * @return float
     */
    public function setAnnualRevenue ($annualRevenue)
    {
        $this->isChanged('annualRevenue', $annualRevenue);
        $this->annualRevenue = $annualRevenue;

        return $this;
    }

    /**
     * Get City
     *
     * @return string
     */
    public function getCity ()
    {
        return $this->city;
    }

    /**
     * Set City
     *
     * @param integer $city
     *
     * @return string
     */
    public function setCity ($city)
    {
        $this->isChanged('city', $city);
        $this->city = $city;

        return $this;
    }

    /**
     * Get Country
     *
     * @return string
     */
    public function getCountry ()
    {
        return $this->country;
    }

    /**
     * Set Country
     *
     * @param integer $country
     *
     * @return string
     */
    public function setCountry ($country)
    {
        $this->isChanged('country', $country);
        $this->country = $country;

        return $this;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return string
     */
    public function setDescription ($description)
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
    public function getDescription ()
    {
        return $this->description;
    }

    /**
     * Get Email
     *
     * @return string
     */
    public function getEmail ()
    {
        return $this->email;
    }

    /**
     * Set Email
     *
     * @param integer $email
     *
     * @return string
     */
    public function setEmail ($email)
    {
        $this->isChanged('email', $email);
        $this->email = $email;

        return $this;
    }

    /**
     * Get Fax
     *
     * @return string
     */
    public function getFax ()
    {
        return $this->fax;
    }

    /**
     * Set Fax
     *
     * @param integer $fax
     *
     * @return string
     */
    public function setFax ($fax)
    {
        $this->isChanged('fax', $fax);
        $this->fax = $fax;

        return $this;
    }

    /**
     * Get Indusrty
     *
     * @return string
     */
    public function getIndusrty ()
    {
        return $this->industry;
    }

    /**
     * Set Indusrty
     *
     * @param integer $industry
     *
     * @return string
     */
    public function setIndusrty ($industry)
    {
        $this->isChanged('industry', $industry);
        $this->industry = $industry;

        return $this;
    }

    /**
     * Add lead
     *
     * @param                                    $key
     * @param \Mautic\CampaignBundle\Entity\Lead $lead
     *
     * @return Account
     */
    public function addLead ($key, Lead $lead)
    {
        $action     = ($this->leads->contains($lead)) ? 'updated' : 'added';
        $leadEntity = $lead->getLead();

        $this->changes['leads'][$action][$leadEntity->getId()] = $leadEntity->getPrimaryIdentifier();
        $this->leads[$key]                                     = $lead;

        return $this;
    }

    /**
     * Remove lead
     *
     * @param Lead $lead
     */
    public function removeLead (Lead $lead)
    {
        $leadEntity                                              = $lead->getLead();
        $this->changes['leads']['removed'][$leadEntity->getId()] = $leadEntity->getPrimaryIdentifier();
        $this->leads->removeElement($lead);
    }

    /**
     * Get leads
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLeads ()
    {
        return $this->leads;
    }


    /**
     * Set name
     *
     * @param string $name
     *
     * @return string
     */
    public function setName ($name)
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
    public function getName ()
    {
        return $this->name;
    }

    /**
     * Set NumberOfEmployees
     *
     * @param string $numberOfEmployees
     *
     * @return integer
     */
    public function setNumberOfEmployees ($numberOfEmployees)
    {
        $this->isChanged('numberOfEmployees', $numberOfEmployees);
        $this->numberOfEmployees = (int)$numberOfEmployees;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getNumberOfEmployees ()
    {
        return $this->numberOfEmployees;
    }

    /**
     * Set Phone
     *
     * @param string $phone
     *
     * @return string
     */
    public function setPhone ($phone)
    {
        $this->isChanged('phone', $phone);
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone ()
    {
        return $this->phone;
    }

    /**
     * Set Rating
     *
     * @param string $rating
     *
     * @return string
     */
    public function setRating ($rating)
    {
        $this->isChanged('rating', $rating);
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return string
     */
    public function getRating ()
    {
        return $this->rating;
    }

    /**
     * Set State
     *
     * @param string $state
     *
     * @return string
     */
    public function setState ($state)
    {
        $this->isChanged('state', $state);
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState ()
    {
        return $this->state;
    }

    /**
     * Set Type
     *
     * @param string $type
     *
     * @return string
     */
    public function setType ($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get Type
     *
     * @return string
     */
    public function getType ()
    {
        return $this->type;
    }

    /**
     * Set Website
     *
     * @param string $website
     *
     * @return string
     */
    public function setWebsite ($website)
    {
        $this->isChanged('website', $website);
        $this->website = $website;

        return $this;
    }

    /**
     * Get Website
     *
     * @return string
     */
    public function getWebsite ()
    {
        return $this->website;
    }

    /**
     * Set Zipcode
     *
     * @param string $zipcode
     *
     * @return string
     */
    public function setZipcode ($zipcode)
    {
        $this->isChanged('zipcode', $zipcode);
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * Get     Zipcode
     *
     * @return string
     */
    public function getZipcode ()
    {
        return $this->zipcode;
    }

}
