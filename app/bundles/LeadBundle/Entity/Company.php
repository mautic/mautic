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
use Mautic\UserBundle\Entity\User;

/**
 * Class Company.
 */
class Company extends FormEntity implements CustomFieldEntityInterface
{
    use CustomFieldEntityTrait;

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
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('company')
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
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        if (isset($this->updatedFields['companyname'])) {
            return $this->updatedFields['companyname'];
        }
        if (!empty($this->fields['core']['companyname']['value'])) {
            return $this->fields['core']['companyname']['value'];
        }

        return '';
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
}
