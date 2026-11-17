<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Form\Type\PointActionFormSubmitType;
use Mautic\FormBundle\Helper\PointActionHelper;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Model\PointModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class PointSubscriber
{
    public function __construct(
        private PointModel $pointModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    #[AsEventListener(
        event: PointBuilderEvent::class,
    )]
    public function onPointBuild(PointBuilderEvent $event): void
    {
        $action = [
            'group'       => 'mautic.form.point.action',
            'label'       => 'mautic.form.point.action.submit',
            'description' => 'mautic.form.point.action.submit_descr',
            'callback'    => [PointActionHelper::class, 'validateFormSubmit'],
            'formType'    => PointActionFormSubmitType::class,
        ];

        $event->addAction('form.submit', $action);
    }

    /**
     * Trigger point actions for form submit.
     */
    #[AsEventListener(
        event: SubmissionEvent::class,
    )]
    public function onFormSubmit(SubmissionEvent $event): void
    {
        if (null === $event->getLead()) {
            return;
        }

        // Keep the IP of a submitter Mautic doesn't track out of the points log
        $this->pointModel->triggerAction('form.submit', $event->getSubmission(), storeIpAddress: !$this->ipLookupHelper->isVisitorUntracked());
    }
}
