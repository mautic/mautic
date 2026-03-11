<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\CompanySegmentRepository;
use Mautic\LeadBundle\Event\CompanySegmentPostSave;
use Mautic\LeadBundle\Event\CompanySegmentPreSave;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<CompanySegment>
 */
class CompanySegmentModel extends FormModel
{
    public function getRepository(): CompanySegmentRepository
    {
        $repository = $this->em->getRepository(CompanySegment::class);
        \assert($repository instanceof CompanySegmentRepository);

        return $repository;
    }

    /**
     * @param CompanySegment $entity
     * @param bool           $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        $isNew = null === $entity->getId();

        // set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);
        assert($entity instanceof CompanySegment);
        $alias = $entity->getAlias();
        if (is_null($alias)) {
            $alias = '';
        }

        $alias = $this->cleanAlias($alias, '', 0, '-');

        // make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $existing  = $repo->getSegments(null, $testAlias, $entity->getId());
        $count     = count($existing);
        $aliasTag  = $count;

        while ($count > 0) {
            $testAlias = $alias.$aliasTag;
            $existing  = $repo->getSegments(null, $testAlias, $entity->getId());
            $count     = count($existing);
            ++$aliasTag;
        }
        if ($testAlias !== $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        $this->dispatchEvent('pre_save', $entity, $isNew);
        $repo->saveEntity($entity);
        $this->dispatchEvent('post_save', $entity, $isNew);
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof CompanySegment) {
            throw new MethodNotAllowedHttpException(['CompanySegment'], 'Entity must be of class CompanySegment()');
        }

        $eventClass = match ($action) {
            'pre_save'      => CompanySegmentPreSave::class,
            'post_save'     => CompanySegmentPostSave::class,
            default         => null,
        };

        if (null === $eventClass && null === $event) {
            throw new \RuntimeException('Either the Event or proper action should be provided.');
        }

        if ($this->dispatcher->hasListeners($eventClass ?? $event::class)) {
            if (null === $event) {
                if (!class_exists($eventClass)) {
                    throw new \RuntimeException('The class '.$eventClass.' does not exist.');
                }

                if (in_array($eventClass, [CompanySegmentPreSave::class, CompanySegmentPostSave::class], true)) {
                    $event = new $eventClass($entity, $this->em, $isNew);
                } else {
                    $event = new $eventClass($entity, $this->em);
                }
            }
            $this->dispatcher->dispatch($event);

            return $event;
        }

        return null;
    }

    public function getPermissionBase(): string
    {
        return 'lead:lists';
    }

    /**
     * @param int|null $id
     */
    public function getEntity($id = null): ?object
    {
        if (null === $id) {
            return new CompanySegment();
        }

        return parent::getEntity($id);
    }
}
