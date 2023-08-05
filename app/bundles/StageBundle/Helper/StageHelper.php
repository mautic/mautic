<?php

namespace Mautic\StageBundle\Helper;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StageHelper
{
    protected LeadModel $leadModel;

    protected StageModel $stageModel;

    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    public function __construct(
        LeadModel $leadModel,
        StageModel $stageModel,
        LoggerInterface $logger,
        TranslatorInterface $translator
        ) {
        $this->leadModel  = $leadModel;
        $this->stageModel = $stageModel;
        $this->logger     = $logger;
        $this->translator = $translator;
    }

    public function changeStage(Lead $lead, Stage $stage, string $origin): void
    {
        // Get the current stage and validate it vs the new one
        $currentStage = ($lead instanceof Lead) ? $lead->getStage() : null;
        if ($currentStage) {
            if ($currentStage->getId() === $stage->getId()) {
                throw new \UnexpectedValueException($this->translator->trans('mautic.stage.campaign.event.already_in_stage'));
            }

            if ($currentStage->getWeight() > $stage->getWeight()) {
                throw new \UnexpectedValueException($this->translator->trans('mautic.stage.campaign.event.stage_invalid'));
            }
        }

        $this->leadModel->addToStage($lead, $stage, $origin);
        $this->leadModel->saveEntity($lead);

        $this->logger->info(
            sprintf(
                'StageBundle: Lead %s changed stage from %s (%s) to %s (%s) by %s',
                $lead->getId(),
                $currentStage ? $currentStage->getName() : 'null',
                $currentStage ? $currentStage->getId() : 'null',
                $stage ? $stage->getName() : 'null',
                $stage ? $stage->getId() : 'null',
                $origin
            )
        );
    }

    public function getStage(int $stageId): Stage
    {
        return $this->stageModel->getEntity($stageId);
    }
}
