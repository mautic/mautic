<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\InternalObject;


use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;

class ContactObject implements ObjectInterface
{
    /**
     * @var LeadModel
     */
    private $model;

    /**
     * @var LeadRepository
     */
    private $repository;

    /**
     * ContactObject constructor.
     *
     * @param LeadModel      $model
     * @param LeadRepository $repository
     */
    public function __construct(LeadModel $model, LeadRepository $repository)
    {
        $this->model      = $model;
        $this->repository = $repository;
    }

    /**
     * @param OrderDAO $syncOrder
     * @param ObjectChangeDAO[] $objects
     */
    public function create(array $objects, OrderDAO $syncOrder)
    {
        foreach ($objects as $object) {
            $contact = new Lead();
            $fields  = $object->getFields();
            foreach ($fields as $field) {
                $contact->addUpdatedField($field->getName(), $field->getValue()->getNormalizedValue());
            }

            $this->model->saveEntity($contact);

            // Todo save mapping

            $this->repository->detachEntity($contact);
        }
    }

    /**
     * @param array             $ids
     * @param ObjectChangeDAO[] $objects
     */
    public function update(array $ids, array $objects)
    {
        /** @var Lead[] $contacts */
        $contacts = $this->model->getEntities(['ids' => $ids]);
        foreach ($contacts as $contact) {
            $changedObjects = $objects[$contact->getId()];

            /** @var ObjectChangeDAO $changedObject */
            foreach ($changedObjects as $changedObject) {
                $fields = $changedObject->getFields();

                foreach ($fields as $field) {
                    $contact->addUpdatedField($field->getName(), $field->getValue()->getNormalizedValue());
                }
            }

            $this->model->saveEntity($contact);
            $this->repository->detachEntity($contact);
        }
    }
}