<?php
/**
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
class Company extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * Used by Mautic to populate the fields pulled from the DB.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Just a place to store updated field values so we don't have to loop through them again comparing.
     *
     * @var array
     */
    private $updatedFields = [];

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
     * @param $name
     *
     * @return bool
     */
    public function __get($name)
    {
        return $this->getFieldValue(strtolower($name));
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

        $builder->createManyToOne('owner', 'Mautic\UserBundle\Entity\User')
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
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
     * @param $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }
    /**
     * Get the primary identifier for the lead.
     *
     * @param bool $lastFirst
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
     * @param bool $ungroup
     *
     * @return array
     */
    public function getFields($ungroup = false)
    {
        if ($ungroup && isset($this->fields['core'])) {
            $return = [];
            foreach ($this->fields as $group => $fields) {
                $return += $fields;
            }

            return $return;
        }

        return $this->fields;
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
     * Add an updated field to persist to the DB and to note changes.
     *
     * @param        $alias
     * @param        $value
     * @param string $oldValue
     */
    public function addUpdatedField($alias, $value, $oldValue = '')
    {
        $value = trim($value);
        if ($value == '') {
            // Ensure value is null for consistency
            $value = null;
        }

        $this->changes['fields'][$alias] = [$oldValue, $value];
        $this->updatedFields[$alias]     = $value;
    }

    /**
     * Get the array of updated fields.
     *
     * @return array
     */
    public function getUpdatedFields()
    {
        return $this->updatedFields;
    }

    /**
     * Get company field value.
     *
     * @param      $field
     * @param null $group
     *
     * @return bool
     */
    public function getFieldValue($field, $group = null)
    {
        if (isset($this->updatedFields[$field])) {
            return $this->updatedFields[$field];
        }

        if (!empty($group) && isset($this->fields[$group][$field])) {
            return $this->fields[$group][$field]['value'];
        }

        foreach ($this->fields as $group => $groupFields) {
            foreach ($groupFields as $name => $details) {
                if ($name == $field) {
                    return $details['value'];
                }
            }
        }

        return false;
    }
}
