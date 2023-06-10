<?php

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Form\Type\PointActionFormSubmitType;
use Mautic\FormBundle\FormEvents;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointSubscriber implements EventSubscriberInterface
{
    public function __construct(private PointModel $pointModel)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::POINT_ON_BUILD => ['onPointBuild', 0],
            FormEvents::FORM_ON_SUBMIT  => ['onFormSubmit', 0],
        ];
    }

    public function onPointBuild(PointBuilderEvent $event)
    {
        $action = [
            'group'       => 'mautic.form.point.action',
            'label'       => 'mautic.form.point.action.submit',
            'description' => 'mautic.form.point.action.submit_descr',
            'callback'    => ['\\Mautic\\FormBundle\\Helper\\PointActionHelper', 'validateFormSubmit'],
            'formType'    => PointActionFormSubmitType::class,
        ];

        $event->addAction('form.submit', $action);
    }

    /**
     * Trigger point actions for form submit.
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        $this->pointModel->triggerAction('form.submit', $event->getSubmission());
    }
}
