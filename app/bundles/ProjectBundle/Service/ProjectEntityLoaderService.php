<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\ProjectBundle\DTO\EntityTypeConfig;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProjectEntityLoaderService
{
    /** @var array<string, EntityTypeConfig> */
    private array $entityTypesCache = [];

    public function __construct(private EntityManagerInterface $em, private TranslatorInterface $translator, private ModelFactory $modelFactory, private CorePermissions $security)
    {
    }

    /**
     * @param array<string, EntityTypeConfig> $entityTypes
     *
     * @return array<string, array<string, mixed>>
     */
    public function getProjectEntities(Project $project, array $entityTypes): array
    {
        $results = [];

        foreach ($entityTypes as $entityType => $config) {
            $repository = $config->model->getRepository();

            $entities   = $repository->createQueryBuilder('e')
                ->join('e.projects', 'p')
                ->where('p.id = :projectId')
                ->setParameter('projectId', $project->getId())
                ->orderBy('e.dateModified', 'DESC')
                ->getQuery()
                ->getResult();

            $results[$entityType] = [
                'label'    => $config->label,
                'entities' => $entities,
                'count'    => count($entities),
            ];
        }

        return $results;
    }

    /**
     * Get entity types filtered by user view permissions.
     *
     * @return array<string, EntityTypeConfig>
     */
    public function getEntityTypesWithViewPermissions(): array
    {
        return $this->filterEntityTypesByPermission('view');
    }

    /**
     * Get entity types filtered by user edit permissions.
     *
     * @return array<string, EntityTypeConfig>
     */
    public function getEntityTypesWithEditPermissions(): array
    {
        return $this->filterEntityTypesByPermission('edit');
    }

    /**
     * Get lookup results for entity type (used by EntityLookupType).
     *
     * @return array<int|string, string>
     */
    public function getLookupResults(string $entityType, string $filter = '', int $limit = 10, int $start = 0, ?int $projectId = null): array
    {
        $entityTypes = $this->getEntityTypes();

        if (!isset($entityTypes[$entityType])) {
            return [];
        }

        $entityConfig = $entityTypes[$entityType];

        // Check permission before proceeding
        if (!$this->hasViewPermissionForEntityType($entityConfig)) {
            return [];
        }
        $repository   = $entityConfig->model->getRepository();

        // Get the label column for this entity type
        $labelColumn = $this->getEntityLabelColumn($entityType);

        $qb = $repository->createQueryBuilder('e')
            ->select('e.id, e.'.$labelColumn.' as name')
            ->setFirstResult($start);

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        // Exclude entities already assigned to the specific project if projectId is provided
        if ($projectId) {
            // Use LEFT JOIN to find entities that are NOT in the specific project
            $qb->leftJoin('e.projects', 'p', 'WITH', 'p.id = :projectId')
               ->andWhere('p.id IS NULL')
               ->setParameter('projectId', $projectId);
        } else {
            // Exclude entities already assigned to any project using QueryBuilder
            // Use LEFT JOIN to find entities that are NOT in any project
            $qb->leftJoin('e.projects', 'p')
               ->andWhere('p.id IS NULL');
        }

        // Add filter if provided
        if (!empty($filter)) {
            $qb->andWhere('e.'.$labelColumn.' LIKE :filter')
                ->setParameter('filter', '%'.$filter.'%');
        }

        $results = $qb->getQuery()->getArrayResult();

        // Format results for ProjectModel (id => name associative array)
        $choices = [];
        foreach ($results as $result) {
            $choices[$result['id']] = $result['name'];
        }

        return $choices;
    }

    /**
     * Check if user has view permission for entity type config.
     */
    public function hasViewPermissionForEntityType(EntityTypeConfig $config): bool
    {
        $permissionBase = $config->model->getPermissionBase();
        $permissions    = [
            $permissionBase.':viewown',
            $permissionBase.':viewother',
        ];

        return $this->security->isGranted($permissions, 'MATCH_ONE');
    }

    /**
     * Check if user has edit permission for entity type config.
     */
    public function hasEditPermissionForEntityType(EntityTypeConfig $config): bool
    {
        $permissionBase = $config->model->getPermissionBase();
        $permissions    = [
            $permissionBase.':editown',
            $permissionBase.':editother',
        ];

        return $this->security->isGranted($permissions, 'MATCH_ONE');
    }

    /**
     * @return array<string, EntityTypeConfig>
     */
    private function getEntityTypes(): array
    {
        if (!empty($this->entityTypesCache)) {
            return $this->entityTypesCache;
        }

        $allMetadata = $this->em->getMetadataFactory()->getAllMetadata();

        foreach ($allMetadata as $metadata) {
            $entityClass = $metadata->getName();

            foreach ($metadata->getAssociationMappings() as $association) {
                if (
                    ClassMetadataInfo::MANY_TO_MANY === $association['type']
                    && Project::class === $association['targetEntity']
                ) {
                    $shortName  = $metadata->getReflectionClass()->getShortName();
                    $entityType = $this->normalizeEntityType(strtolower($shortName));

                    $this->entityTypesCache[$entityType] = new EntityTypeConfig(
                        entityClass: $entityClass,
                        label: $this->getEntityLabel($entityType),
                        model: $this->findModelForEntityType($entityType),
                    );

                    break;
                }
            }
        }

        // Sort entity types alphabetically by label
        uasort($this->entityTypesCache, fn (EntityTypeConfig $a, EntityTypeConfig $b) => strcasecmp($a->label, $b->label));

        return $this->entityTypesCache;
    }

    /**
     * Filter entity types by permission type.
     *
     * @return array<string, EntityTypeConfig>
     */
    private function filterEntityTypesByPermission(string $permissionType): array
    {
        $allEntityTypes     = $this->getEntityTypes();
        $allowedEntityTypes = [];

        foreach ($allEntityTypes as $entityType => $config) {
            $hasPermission = 'view' === $permissionType
                ? $this->hasViewPermissionForEntityType($config)
                : $this->hasEditPermissionForEntityType($config);

            if ($hasPermission) {
                $allowedEntityTypes[$entityType] = $config;
            }
        }

        return $allowedEntityTypes;
    }

    /**
     * Get the label column name for an entity type.
     */
    private function getEntityLabelColumn(string $entityType): string
    {
        return match ($entityType) {
            'asset', 'page' => 'title',
            default         => 'name',
        };
    }

    /**
     * Normalize entity type names for consistent usage.
     */
    private function normalizeEntityType(string $entityType): string
    {
        return match ($entityType) {
            'leadlist' => 'segment',
            'lead'     => 'contact',
            default    => $entityType,
        };
    }

    private function getEntityLabel(string $entityType): string
    {
        // Create the translation key
        $translationKeyString = "mautic.{$entityType}.{$entityType}";

        // Get the translation
        $translated = $this->translator->trans($translationKeyString);

        // If translation doesn't exist (returns the key itself), return capitalized entity type
        if ($translated === $translationKeyString) {
            return ucfirst($entityType);
        }

        // Return the actual translation
        return $translated;
    }

    private function findModelForEntityType(string $entityType): FormModel
    {
        // Map entity types to their model keys
        $modelKey = match ($entityType) {
            'segment' => 'lead.list',
            'message' => 'channel.message',
            'company' => 'lead.company',
            default   => $entityType,
        };

        $model = $this->modelFactory->getModel($modelKey);
        \assert($model instanceof FormModel);

        return $model;
    }
}
