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
    public const TABLE_NAME  = 'companies';

    /**
     * @var int
     */
    private $id;

    /**
     * @var int|null
     */
    private $score = 0;

    private ?User $owner = null;

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
    public function setSocialCache($cache): void
    {
        $this->socialCache = $cache;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(CompanyRepository::class);

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
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
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
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

    protected function isChanged($prop, $val)
    {
        $prefix = 'company';

        if (str_starts_with($prop, $prefix)) {
            $getter  = 'get'.ucfirst(substr($prop, strlen($prefix)));
            $current = $this->$getter();
            if ($current !== $val) {
                $this->addChange($prop, [$current, $val]);
            }
        } elseif ('owner' === $prop) {
            $current = $this->getOwner();
            if ($current && !$val) {
                $this->changes['owner'] = [$current->getName().' ('.$current->getId().')', $val];
            } elseif (!$current && $val) {
                $this->changes['owner'] = [$current, $val->getName().' ('.$val->getId().')'];
            } elseif ($current && $current->getId() != $val->getId()) {
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
     * @return Company
     */
    public function setOwner(User $owner = null)
    {
        $this->isChanged('owner', $owner);
        $this->owner = $owner;

        return $this;
    }

    public function getOwner(): ?User
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
        return $this->getOwner() ?? $this->getCreatedBy();
    }

    /**
     * @param int $score
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
        $this->isChanged('companyname', $name);
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
        $this->isChanged('companyemail', $email);
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
        $this->isChanged('companyaddress1', $address1);
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
        $this->isChanged('companyaddress2', $address2);
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
        $this->isChanged('companyphone', $phone);
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
        $this->isChanged('companycity', $city);
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
        $this->isChanged('companystate', $state);
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
        $this->isChanged('companyzipcode', $zipcode);
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
        $this->isChanged('companycountry', $country);
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
        $this->isChanged('companywebsite', $website);
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
        $this->isChanged('companyindustry', $industry);
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
        $this->isChanged('companydescription', $description);
        $this->description = $description;

        return $this;
    }
}
