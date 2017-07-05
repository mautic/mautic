<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;

class OwnerImport extends AbstractImport
{
    public function create(array $data = [])
    {
        $pipedriveOwner = $this->em->getRepository(PipedriveOwner::class)->findOneByOwnerId($data['id']);

        if (!$pipedriveOwner) {
            $pipedriveOwner = new PipedriveOwner();
        }

        $pipedriveOwner->setEmail($data['email']);
        $pipedriveOwner->setOwnerId($data['id']);

        $this->em->persist($pipedriveOwner);
        $this->em->flush();

        return true;
    }
}
