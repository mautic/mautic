<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Form\Validator\Constraints\UniqueCustomField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Company extends FormEntity implements CustomFieldEntityInterface, IdentifierFieldEntityInterface
{
    use CustomFieldEntityTrait;

    public const FIELD_ALIAS = 'company';

    /**
     * @var int
     */
    private $id;

    /**
     * @var int|null
     */
    private $score = 0;

    /**
     * @var User|null
     */
    private $owner;

    /**
     * @var array<string, array<string, string>>
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
     * @return array<string, array<string, string>>
     */
    public function getSocialCache()
    {
        return $this->socialCache;
    }

    /**
     * @param array<string, array<string, string>> $cache
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
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createField('socialCache', 'array')
            ->columnName('social_cache')
            ->nullable()
            ->build();

        $builder->createManyToOne('owner', 'Mautic\UserBundle\Entity\User')
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

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new UniqueCustomField(['object' => 'company']));
    }

    public static function getDefaultIdentifierFields(): array
    {
        return [
            'companyname',
            'companyemail',
            'companywebsite',
            'city',
            'state',
            'country',
        ];
    }

    /**
     * @param string|User                                                       $prop
     * @param int|string|User|Company|array<string, array<string, string>>|null $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ('owner' == $prop) {
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
            parent::isChanged($prop, $val);
        }
    }

    /**
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
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Company
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Company
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param string $address1
     *
     * @return Company
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param string $address2
     *
     * @return Company
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     *
     * @return Company
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return Company
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return Company
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @param string $zipcode
     *
     * @return Company
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return Company
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $website
     *
     * @return Company
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return string
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * @param string $industry
     *
     * @return Company
     */
    public function setIndustry($industry)
    {
        $this->industry = $industry;

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
     *
     * @return Company
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }
}
