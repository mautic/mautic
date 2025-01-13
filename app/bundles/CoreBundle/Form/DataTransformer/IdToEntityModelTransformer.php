<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<array<mixed>|int|string, array<mixed>|int|string>
 */
class IdToEntityModelTransformer implements DataTransformerInterface
{
    /**
     * @param class-string $repository
     */
    public function __construct(private EntityManagerInterface $em, private string $repository, private string $id = 'id', private bool $isArray = false)
    {
    }

    /**
     * @param array<mixed>|object|null $entity
     *
     * @return array<mixed>|int|string
     */
    public function transform($entity)
    {
        $func = 'get'.ucfirst($this->id);

        if (!$this->isArray) {
            if (is_null($entity) || !is_object($entity) || !method_exists($entity, $func)) {
                return '';
            }

            return $entity->$func();
        }

        if (is_null($entity) && !is_array($entity) && !$entity instanceof PersistentCollection) {
            return [];
        }

        $return = [];
        foreach ($entity as $e) {
            $return[] = $e->$func();
        }

        return $return;
    }

    /**
     * @param array<mixed>|int|string $id
     *
     * @return array<mixed>|object|null
     */
    public function reverseTransform($id)
    {
        if (!$this->isArray) {
            if (!$id) {
                return null;
            }

            $entity = $this->em
                ->getRepository($this->repository)
                ->findOneBy([$this->id => $id]);

            if (null === $entity) {
                throw new TransformationFailedException(sprintf('An entity with a/an '.$this->id.' of "%s" does not exist!', $id));
            }

            return $entity;
        }

        if (empty($id) || !is_array($id)) {
            return [];
        }

        $repo   = $this->em->getRepository($this->repository);
        $prefix = $repo->getTableAlias();

        $entities = $repo->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => $prefix.'.'.$this->id,
                        'expr'   => 'in',
                        'value'  => $id,
                    ],
                ],
            ],
            'ignore_paginator' => true,
        ]);

        if (!count($entities)) {
            throw new TransformationFailedException(sprintf('Entities with a/an '.$this->id.' of "%s" does not exist!', json_encode($id)));
        }

        return $entities;
    }

    /**
     * Set the repository to use.
     *
     * @param string $repo
     */
    public function setRepository($repo): void
    {
        $this->repository = $repo;
    }

    /**
     * Set the identifier to use.
     *
     * @param string $id
     */
    public function setIdentifier($id): void
    {
        $this->id = $id;
    }
}
