<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Custom processor for PUT operations to ensure entities are replaced instead of created.
 *
 * This processor decorates the default persist processor and intercepts PUT operations
 * to load existing entities from the database and completely replace them with incoming data,
 * following proper HTTP PUT semantics. It applies globally to all API Platform entities.
 */
final class PutProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Only handle PUT operations with an ID in the URI and valid entity data
        if (!$operation instanceof Put || !isset($uriVariables['id']) || !is_object($data)) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Load the existing entity from the database
        $existingEntity = $this->entityManager->find($data::class, $uriVariables['id']);

        if (null === $existingEntity) {
            // Entity doesn't exist, let the default processor create it
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // For PUT operations, we want to replace the existing entity completely
        // The incoming $data contains the new state that should replace the existing entity
        // Following HTTP PUT semantics: completely replace the resource

        $this->mergeEntityData($data, $existingEntity, $data::class);

        // Persist the changes
        $this->entityManager->persist($existingEntity);
        $this->entityManager->flush();

        return $existingEntity;
    }

    /**
     * Replace data from the incoming entity into the existing entity.
     *
     * For PUT operations, we completely replace the resource with the provided data,
     * including setting fields to null if they're not provided in the request.
     *
     * @param class-string $entityClass
     */
    private function mergeEntityData(object $sourceEntity, object $targetEntity, string $entityClass): void
    {
        // Get the entity metadata to know which properties to update
        $metadata = $this->entityManager->getClassMetadata($entityClass);

        // Update regular fields
        foreach ($metadata->getFieldNames() as $fieldName) {
            if (!$metadata->isIdentifier($fieldName)) {
                $this->updateEntityField($sourceEntity, $targetEntity, $fieldName);
            }
        }

        // Update associations
        foreach ($metadata->getAssociationNames() as $associationName) {
            $this->updateEntityField($sourceEntity, $targetEntity, $associationName);
        }
    }

    /**
     * Replace a single field/association on the target entity from the source entity.
     *
     * For PUT operations, we replace the entire resource, so we set the value
     * from the source entity regardless of whether it's null or not.
     */
    private function updateEntityField(object $sourceEntity, object $targetEntity, string $fieldName): void
    {
        $getter = 'get'.ucfirst($fieldName);
        $setter = 'set'.ucfirst($fieldName);

        if (method_exists($sourceEntity, $getter) && method_exists($targetEntity, $setter)) {
            $value = $sourceEntity->$getter();
            // For PUT, we replace the entire resource, so set the value even if it's null
            $targetEntity->$setter($value);
        }
    }
}
