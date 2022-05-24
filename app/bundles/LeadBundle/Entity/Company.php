<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\UserBundle\Entity\User;

class Company extends FormEntity implements CustomFieldEntityInterface
{
    use CustomFieldEntityTrait;

    const FIELD_ALIAS = 'company';

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var int|null
     */
    private $score = 0;

    /**
     * @var User
     */
    private $owner;

    /**
     * @var mixed[]
     */
    private $socialCache = [];

    private $email;

    private $address1;

    private $address2;

    private $phone;

    private $city;

    private $state;

    private $zipcode;

    private $country;

    private $name;

    private $website;

    private $industry;

    private $description;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * @return mixed[]
     */
    public function getSocialCache()
    {
        return $this->socialCache;
    }

    /**
     * @param mixed[] $cache
     */
    public function setSocialCache($cache)
    {
        $this->socialCache = $cache;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('companies')
            ->setCustomRepositoryClass(CompanyRepository::class);

        $builder->createField('id', 'integer')
            ->isPrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createField('socialCache', 'array')
            ->columnName('social_cache')
            ->nullable()
            ->build();

        $builder->createManyToOne('owner', User::class)
            ->cascadeMerge()
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createField('score', 'integer')
            ->nullable()
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
        if ('owner' === $prop) {
            /** @var User|null $newOwner */
            $newOwner     = $val;
            $currentOwner = $this->getOwner();
            if ($currentOwner && $newOwner) {
                if ((int) $currentOwner->getId() === (int) $newOwner->getId()) {
                    unset($this->changes['owner']);
                } else {
                    $this->changes['owner'] = [$currentOwner->getId(), $newOwner->getId()];
                }
            } elseif ($currentOwner) {
                $this->changes['owner'] = [$currentOwner->getId(), $newOwner];
            } elseif ($newOwner) {
                $this->changes['owner'] = [$currentOwner, $newOwner->getId()];
            }

            return;
        }

        parent::isChanged($prop, $val);
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the primary identifier for the company.
     *
     * @return string|null
     */
    public function getPrimaryIdentifier()
    {
        if ($name = $this->getName()) {
            return $name;
        } elseif (!empty($this->fields['core']['companyemail']['value'])) {
            return $this->fields['core']['companyemail']['value'];
        }

        return null;
    }

    /**
     * @return Company
     */
    public function setOwner(User $owner = null)
    {
        $this->isChanged('owner', $owner);
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return User|null
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
     * @param int|null $score
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
     * @return int|null
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return Company
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return Company
     */
    public function setEmail($email)
    {
        $this->isChanged('email', $email);
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param string|null $address1
     *
     * @return Company
     */
    public function setAddress1($address1)
    {
        $this->isChanged('address1', $address1);
        $this->address1 = $address1;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param string|null $address2
     *
     * @return Company
     */
    public function setAddress2($address2)
    {
        $this->isChanged('address2', $address2);
        $this->address2 = $address2;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     *
     * @return Company
     */
    public function setPhone($phone)
    {
        $this->isChanged('phone', $phone);
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     *
     * @return Company
     */
    public function setCity($city)
    {
        $this->isChanged('city', $city);
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string|null $state
     *
     * @return Company
     */
    public function setState($state)
    {
        $this->isChanged('state', $state);
        $this->state = $state;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @param string|null $zipcode
     *
     * @return Company
     */
    public function setZipcode($zipcode)
    {
        $this->isChanged('zipcode', $zipcode);
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     *
     * @return Company
     */
    public function setCountry($country)
    {
        $this->isChanged('country', $country);
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string|null $website
     *
     * @return Company
     */
    public function setWebsite($website)
    {
        $this->isChanged('website', $website);
        $this->website = $website;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * @param string|null $industry
     *
     * @return Company
     */
    public function setIndustry($industry)
    {
        $this->isChanged('industry', $industry);
        $this->industry = $industry;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return Company
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }
}
