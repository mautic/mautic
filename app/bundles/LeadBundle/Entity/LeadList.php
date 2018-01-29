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
use Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAlias;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class LeadList.
 */
class LeadList extends FormEntity
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
    private $alias;

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var bool
     */
    private $isGlobal = true;

    /**
     * @var ArrayCollection
     */
    private $leads;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->leads = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_lists')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\LeadListRepository');

        $builder->addIdColumns();

        $builder->addField('alias', 'string');

        $builder->addField('filters', 'array');

        $builder->createField('isGlobal', 'boolean')
            ->columnName('is_global')
            ->build();

        $builder->createOneToMany('leads', 'ListLead')
            ->setIndexBy('id')
            ->mappedBy('list')
            ->fetchExtraLazy()
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            ['message' => 'mautic.core.name.required']
        ));

        $metadata->addConstraint(new UniqueUserAlias([
            'field'   => 'alias',
            'message' => 'mautic.lead.list.alias.unique',
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('leadList')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'alias',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'filters',
                    'isGlobal',
                ]
            )
            ->build();
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
     * Set name.
     *
     * @param int $name
     *
     * @return LeadList
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return int
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return LeadList
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set filters.
     *
     * @param array $filters
     *
     * @return LeadList
     */
    public function setFilters(array $filters)
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set isGlobal.
     *
     * @param bool $isGlobal
     *
     * @return LeadList
     */
    public function setIsGlobal($isGlobal)
    {
        $this->isChanged('isGlobal', $isGlobal);
        $this->isGlobal = $isGlobal;

        return $this;
    }

    /**
     * Get isGlobal.
     *
     * @return bool
     */
    public function getIsGlobal()
    {
        return $this->isGlobal;
    }

    /**
     * Proxy function to getIsGlobal().
     *
     * @return bool
     */
    public function isGlobal()
    {
        return $this->getIsGlobal();
    }

    /**
     * Set alias.
     *
     * @param string $alias
     *
     * @return LeadList
     */
    public function setAlias($alias)
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Get leads.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * Clone entity with empty contact list.
     */
    public function __clone()
    {
        parent::__clone();

        $this->id    = null;
        $this->leads = new ArrayCollection();
    }
}
