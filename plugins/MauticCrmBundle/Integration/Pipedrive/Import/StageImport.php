<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use MauticPlugin\MauticCrmBundle\Entity\PipedrivePipeline;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveStage;
use Symfony\Component\HttpFoundation\Response;

class StageImport extends AbstractImport
{
    public function create(array $data = [])
    {
        $pipeline = $this->em->getRepository(PipedrivePipeline::class)->findOneByPipelineId($data['pipeline_id']);
        if (!$pipeline) {
            throw new \Exception('Pipeline for stage doesn\'t exist', Response::HTTP_NOT_FOUND);
        }

        $stage = $this->em->getRepository(PipedriveStage::class)->findOneByStageId($data['id']);
        if (!$stage) {
            $stage = new PipedriveStage();
        }

        $stage->setStageId($data['id']);
        $stage->setPipeline($pipeline);
        $stage->setName($data['name']);
        $stage->setActive($data['active_flag']);
        $stage->setOrder($data['order_nr']);

        $this->em->persist($stage);
        $this->em->flush();

        return true;
    }

    public function update(array $data = [])
    {
        if (!$this->getIntegration()->isDealSupportEnabled()) {
            return; //feature disabled
        }

        $stage = $this->em->getRepository(PipedriveStage::class)->findOneByStageId($data['id']);

        if (!$stage) {
            return $this->create($data);
        }

        $update = false;
        foreach ($data as $field => $value) {
            switch ($field) {
                case 'name':
                    if ($value != $stage->getName()) {
                        $stage->setName($value);
                        $update = true;
                    }
                    break;
                case 'active':
                    if ($value != $stage->isActive()) {
                        $stage->setActive($value);
                        $update = true;
                    }
                    break;
                case 'order_nr':
                    if ($value != $stage->getOrder()) {
                        $stage->setOrder($value);
                        $update = true;
                    }
                    break;
            }
        }

        if ($update) {
            $this->em->persist($stage);
            $this->em->flush();
        }
    }

    public function delete(array $data = [])
    {
        if (!$this->getIntegration()->isDealSupportEnabled()) {
            return; //feature disabled
        }

        $stage = $this->em->getRepository(PipedriveStage::class)->findOneByStageId($data['id']);

        if (!$stage) {
            throw new \Exception('Stage doesn\'t exist', Response::HTTP_NOT_FOUND);
        }

        $this->em->transactional(function ($em) use ($stage) {
            $em->remove($stage);
        });
    }
}
