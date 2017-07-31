<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use MauticPlugin\MauticCrmBundle\Entity\PipedrivePipeline;

class PipelineImport extends AbstractImport
{
    public function create(array $data = [])
    {
        $pipeline = $this->em->getRepository(PipedrivePipeline::class)->findOneByPipelineId($data['id']);

        if (!$pipeline) {
            $pipeline = new PipedrivePipeline();
        }

        $pipeline->setPipelineId($data['id']);
        $pipeline->setName($data['name']);
        $pipeline->setActive($data['active']);

        $this->em->persist($pipeline);
        $this->em->flush();

        return true;
    }

    public function update(array $data = [])
    {
        if (!$this->getIntegration()->isDealSupportEnabled()) {
            return; //feature disabled
        }

        $pipeline = $this->em->getRepository(PipedrivePipeline::class)->findOneByPipelineId($data['id']);

        if (!$pipeline) {
            return $this->create($data);
        }

        $update = false;
        foreach ($data as $field => $value) {
            switch($field) {
                case 'name':
                    if ($value != $pipeline->getName()) {
                        $pipeline->setName($value);
                        $update = true;

                    }
                    break;
                case 'active':
                    if ($value != $pipeline->isActive()) {
                        $pipeline->setActive($value);
                        $update = true;
                    }
                    break;
            }
        }

        if ($update) {
            $this->em->persist($pipeline);
            $this->em->flush();
        }
    }

    public function delete(array $data = [])
    {
        if (!$this->getIntegration()->isDealSupportEnabled()) {
            return; //feature disabled
        }

        $pipeline = $this->em->getRepository(PipedrivePipeline::class)->findOneByPipelineId($data['id']);

        if (!$pipeline) {
            throw new \Exception('Pipeline doesn\'t exist', Response::HTTP_NOT_FOUND);
        }

        $this->em->transactional(function ($em) use ($pipeline) {
            $em->remove($pipeline);
        });
    }
}
