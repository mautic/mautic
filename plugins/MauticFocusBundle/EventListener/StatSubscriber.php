<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class StatSubscriber implements EventSubscriberInterface
{
    /**
     * @var FocusModel
     */
    protected $model;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param FocusModel   $model
     * @param RequestStack $requestStack
     */
    public function __construct(FocusModel $model, RequestStack $requestStack)
    {
        $this->model        = $model;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_HIT    => ['onPageHit', 0],
            FormEvents::FORM_ON_SUBMIT => ['onFormSubmit', 0],
        ];
    }

    /**
     * @param PageHitEvent $event
     */
    public function onPageHit(PageHitEvent $event)
    {
        $hit    = $event->getHit();
        $source = $hit->getSource();

        if ($source == 'focus' || $source == 'focus.focus') {
            $sourceId = $hit->getSourceId();
            $focus    = $this->model->getEntity($sourceId);

            if ($focus && $focus->isPublished()) {
                $this->model->addStat($focus, Stat::TYPE_CLICK, $hit, $hit->getLead());
            }
        }
    }

    /**
     * Note if this submission is from a focus submit.
     *
     * @param SubmissionEvent $event
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        // Check the request for a focus field
        $mauticform = $this->requestStack->getCurrentRequest()->request->get('mauticform', []);
        $id         = $mauticform['focusId'] ?? false;

        if (!empty($id)) {
            $focus = $this->model->getEntity($id);

            if ($focus && $focus->isPublished()) {
                // Make sure the form is still applicable
                $form = $event->getSubmission()->getForm();
                if ((int) $form->getId() === (int) $focus->getForm()) {
                    $this->model->addStat($focus, Stat::TYPE_FORM, $event->getSubmission(), $event->getLead());
                }
            }
        }
    }
}
