<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveStage;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveDeal;
use Symfony\Component\HttpFoundation\Response;

class DealImport extends AbstractImport
{
    public function create(array $data = [])
    {
        $stage = $this->em->getRepository(PipedriveStage::class)->findOneByStageId($data['stage_id']);
        if (!$stage) {
            throw new \Exception('Stage for deal doesn\'t exist', Response::HTTP_NOT_FOUND);
        }

        if (isset($data['person_id']['value'])) {
            $leadIntegrationEntity = $this->getLeadIntegrationEntity([
                'integrationEntityId' => $data['person_id']['value'],
            ]);
            $lead  = $this->em->getRepository(Lead::class)->findOneById($leadIntegrationEntity->getInternalEntityId());
        } else {
            throw new \Exception('Person for deal not defined on pipedrive', Response::HTTP_NOT_FOUND);
        }

        if (!$lead) {
            throw new \Exception("Lead for deal doesn't exist", Response::HTTP_NOT_FOUND);
        }

        $deal = $this->em->getRepository(PipedriveDeal::class)->findOneByDealId($data['id']);
        if (!$deal) {
            $deal = new PipedriveDeal();
        }

        $deal->setDealId($data['id']);
        $deal->setStage($stage);
        $deal->setTitle($data['title']);
        $deal->setLead($lead);

        $this->em->persist($deal);
        $this->em->flush();

        return true;
    }

    public function update(array $data = [])
    {
        if (!$this->getIntegration()->isDealSupportEnabled()) {
            return; //feature disabled
        }

        $deal = $this->em->getRepository(PipedriveDeal::class)->findOneByDealId($data['id']);

        if (!$deal) {
            return $this->create($data);
        }

        $update = false;
        foreach ($data as $field => $value) {
            switch ($field) {
                case 'title':
                    if ($value != $deal->getTitle()) {
                        $deal->setTitle($value);
                        $update = true;
                    }
                    break;
                case 'stage_id':
                    $stage = $this->em->getRepository(PipedriveStage::class)->findOneByStageId($value);
                    if (!$stage) {
                        throw new \Exception('Stage for deal doesn\'t exist', Response::HTTP_NOT_FOUND);
                    }
                    if ($stage != $deal->getStage()) {
                        $deal->setStage($stage);
                        $update = true;
                    }
                    break;
                case 'person_id':
                    $leadIntegrationEntity = $this->getLeadIntegrationEntity([
                        'integrationEntityId' => $value
                    ]);
                    $lead  = $this->em->getRepository(Lead::class)->findOneById($leadIntegrationEntity->getInternalEntityId());

                    if (!$lead) {
                        throw new \Exception("Lead for deal doesn't exist", Response::HTTP_NOT_FOUND);
                    }

                    if ($lead != $deal->getLead()) {
                        $deal->setLead($lead);
                        $update = true;
                    }
                    break;
            }
        }

        if ($update) {
            $this->em->persist($deal);
            $this->em->flush();
        }
    }

    public function delete(array $data = [])
    {
        if (!$this->getIntegration()->isDealSupportEnabled()) {
            return; //feature disabled
        }

        $deal = $this->em->getRepository(PipedriveDeal::class)->findOneByDealId($data['id']);

        if (!$deal) {
            throw new \Exception('Deal doesn\'t exist', Response::HTTP_NOT_FOUND);
        }

        $this->em->transactional(function ($em) use ($deal) {
            $em->remove($deal);
        });
    }
}
