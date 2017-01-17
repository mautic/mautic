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

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\UserBundle\Entity\User;

/**
 * Class Company.
 */
class Company extends FormEntity implements CustomFieldEntityInterface
{
    use CustomFieldEntityTrait;

    const FIELD_ALIAS = 'company';

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $score = 0;

    /**
     * @var \Mautic\UserBundle\Entity\User
     */
    private $owner;

    /**
     * @var array
     */
    private $socialCache = [];

    /**
     * @var
     */
    private $email;

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
    private $phone;

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
     * @var
     */
    private $country;

    /**
     * @var
     */
    private $name;

    /**
     * @var
     */
    private $website;

    /**
     * @var
     */
    private $industry;

    /**
     * @var
     */
    private $description;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
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
     * Set social cache.
     *
     * @param $cache
     */
    public function setSocialCache($cache)
    {
        $this->socialCache = $cache;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('companies')
                ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\CompanyRepository');

        $builder->createField('id', 'integer')
                ->isPrimaryKey()
                ->generatedValue()
                ->build();

        $builder->createField('socialCache', 'array')
                ->columnName('social_cache')
                ->nullable()
                ->build();

        $builder->createManyToOne('owner', 'Mautic\UserBundle\Entity\User')
                ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
                ->build();

        $builder->createField('score', 'integer')
                ->build();

        self::loadFixedFieldMetadata(
            $builder,
            [
                'email',
                'address1',
                'address2',
                'phone',
                'city',
                'state',
                'zipcode',
                'country',
                'name',
                'website',
                'industry',
                'description',
            ],
            FieldModel::$coreCompanyFields
        );
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('companyBasic')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'email',
                    'address1',
                    'address2',
                    'phone',
                    'city',
                    'state',
                    'zipcode',
                    'country',
                    'website',
                    'industry',
                    'description',
                    'score',
                ]
            )
            ->setGroupPrefix('company')
            ->addListProperties(
                [
                    'id',
                    'fields',
                    'score',
                ]
            )
            ->build();
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'owner') {
            if ($current && !$val) {
                $this->changes['owner'] = [$current->getName().' ('.$current->getId().')', $val];
            } elseif (!$current && $val) {
                $this->changes['owner'] = [$current, $val->getName().' ('.$val->getId().')'];
            } elseif ($current && $val && $current->getId() != $val->getId()) {
                $this->changes['owner'] = [
                    $current->getName().'('.$current->getId().')',
                    $val->getName().'('.$val->getId().')',
                ];
            }
        } else {
            $this->changes[$prop] = [$current, $val];
        }
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
     * Get the primary identifier for the company.
     *
     * @return string
     */
    public function getPrimaryIdentifier()
    {
        if ($name = $this->getName()) {
            return $name;
        } elseif (!empty($this->fields['core']['companyemail']['value'])) {
            return $this->fields['core']['companyemail']['value'];
        }
    }

    /**
     * Set owner.
     *
     * @param User $owner
     *
     * @return Company
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
     * Set score.
     *
     * @param User $score
     *
     * @return Company
     */
    public function setScore($score)
    {
        $score = (int) $score;

        $this->isChanged('score', $score);
        $this->score = $score;

        return $this;
    }

    /**
     * Get score.
     *
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return Company
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return Company
     */
    public function setEmail($email)
    {
        $this->email = $email;

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
     * @return Company
     */
    public function setAddress1($address1)
    {
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
     * @return Company
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;

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
     * @return Company
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

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
     * @return Company
     */
    public function setCity($city)
    {
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
     * @return Company
     */
    public function setState($state)
    {
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
     * @return Company
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;

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
     * @return Company
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param mixed $website
     *
     * @return Company
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * @param mixed $industry
     *
     * @return Company
     */
    public function setIndustry($industry)
    {
        $this->industry = $industry;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     *
     * @return Company
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }
}
