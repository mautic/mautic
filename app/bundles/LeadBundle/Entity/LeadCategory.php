<?php

namespace Mautic\LeadBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Contact Category',
    operations: [
        new GetCollection(uriTemplate: '/contactcategories', security: "is_granted('lead:leads:viewown')"),
        new Post(uriTemplate: '/contactcategories', security: "is_granted('lead:leads:create')"),
        new Get(uriTemplate: '/contactcategories/{id}', security: "is_granted('lead:leads:viewown')"),
        new Put(uriTemplate: '/contactcategories/{id}', security: "is_granted('lead:leads:editown')"),
        new Patch(uriTemplate: '/contactcategories/{id}', security: "is_granted('lead:leads:editother')"),
        new Delete(uriTemplate: '/contactcategories/{id}', security: "is_granted('lead:leads:deleteown')"),
    ],
    normalizationContext: [
        'groups'                  => ['leadcategory:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['leadcategory:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class LeadCategory
{
    /**
     * @var int
     */
    #[Groups(['leadcategory:read'])]
    private $id;

    /**
     * @var Category
     **/
    #[Groups(['leadcategory:read', 'leadcategory:write'])]
    private $category;

    /**
     * @var Lead
     */
    #[Groups(['leadcategory:read', 'leadcategory:write'])]
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['leadcategory:read', 'leadcategory:write'])]
    private $dateAdded;

    /**
     * @var bool
     */
    #[Groups(['leadcategory:read', 'leadcategory:write'])]
    private $manuallyRemoved = false;

    /**
     * @var bool
     */
    #[Groups(['leadcategory:read', 'leadcategory:write'])]
    private $manuallyAdded = false;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_categories')
            ->setCustomRepositoryClass(LeadCategoryRepository::class);

        $builder->addId();

        $builder->createManyToOne('category', Category::class)
            ->addJoinColumn('category_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', false);

        $builder->addDateAdded();

        $builder->createField('manuallyRemoved', 'boolean')
            ->columnName('manually_removed')
            ->build();

        $builder->createField('manuallyAdded', 'boolean')
            ->columnName('manually_added')
            ->build();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $date
     */
    public function setDateAdded($date): void
    {
        $this->dateAdded = $date;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    /**
     * @return bool
     */
    public function getManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @param bool $manuallyRemoved
     */
    public function setManuallyRemoved($manuallyRemoved): void
    {
        $this->manuallyRemoved = $manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function wasManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function getManuallyAdded()
    {
        return $this->manuallyAdded;
    }

    /**
     * @param bool $manuallyAdded
     */
    public function setManuallyAdded($manuallyAdded): void
    {
        $this->manuallyAdded = $manuallyAdded;
    }

    /**
     * @return bool
     */
    public function wasManuallyAdded()
    {
        return $this->manuallyAdded;
    }
}
