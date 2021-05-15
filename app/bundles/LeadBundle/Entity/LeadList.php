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
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAlias;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class LeadList extends FormEntity
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $publicName;

    /**
     * @var Category
     **/
    private $category;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string|null
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
     * @var bool
     */
    private $isPreferenceCenter = false;

    /**
     * @var ArrayCollection
     */
    private $leads;

    public function __construct()
    {
        $this->leads = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_lists')
            ->setCustomRepositoryClass(LeadListRepository::class);

        $builder->addIdColumns();

        $builder->addField('alias', 'string');

        $builder->createField('publicName', 'string')
            ->columnName('public_name')
            ->build();

        $builder->addCategory();

        $builder->addField('filters', 'array');

        $builder->createField('isGlobal', 'boolean')
            ->columnName('is_global')
            ->build();

        $builder->createField('isPreferenceCenter', 'boolean')
            ->columnName('is_preference_center')
            ->build();

        $builder->createOneToMany('leads', 'ListLead')
            ->setIndexBy('id')
            ->mappedBy('list')
            ->fetchExtraLazy()
            ->build();
    }

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
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('leadList')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'publicName',
                    'alias',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'filters',
                    'isGlobal',
                    'isPreferenceCenter',
                ]
            )
            ->build();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|null $name
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
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $description
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
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set category.
     */
    public function setCategory(Category $category = null): LeadList
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Get publicName.
     *
     * @return string|null
     */
    public function getPublicName()
    {
        return $this->publicName;
    }

    /**
     * @param string|null $publicName
     *
     * @return LeadList
     */
    public function setPublicName($publicName)
    {
        $this->isChanged('publicName', $publicName);
        $this->publicName = $publicName;

        return $this;
    }

    /**
     * @return LeadList
     */
    public function setFilters(array $filters)
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        if (is_array($this->filters)) {
            return $this->addLegacyParams($this->filters);
        }

        return $this->filters;
    }

    /**
     * @param bool $isGlobal
     *
     * @return LeadList
     */
    public function setIsGlobal($isGlobal)
    {
        $this->isChanged('isGlobal', (bool) $isGlobal);
        $this->isGlobal = (bool) $isGlobal;

        return $this;
    }

    /**
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
     * @param string|null $alias
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
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
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
        $this->setIsPublished(false);
        $this->setAlias('');
    }

    /**
     * @return bool
     */
    public function getIsPreferenceCenter()
    {
        return $this->isPreferenceCenter;
    }

    /**
     * @param bool $isPreferenceCenter
     */
    public function setIsPreferenceCenter($isPreferenceCenter)
    {
        $this->isChanged('isPreferenceCenter', (bool) $isPreferenceCenter);
        $this->isPreferenceCenter = (bool) $isPreferenceCenter;
    }

    /**
     * @deprecated remove after several of years.
     *
     * This is needed go keep BC after we moved 'filter' and 'display' params
     * to the 'properties' array.
     */
    private function addLegacyParams(array $filters): array
    {
        return array_map(
            function (array $filter) {
                $filter['filter'] = $filter['properties']['filter'] ?? $filter['filter'] ?? null;
                $filter['display'] = $filter['properties']['display'] ?? $filter['display'] ?? null;

                return $filter;
            },
            $filters
        );
    }
}
