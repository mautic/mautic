<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Helper\EventListener;

use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class NewlyCreatedFormSubmissionSubscriber.
 */
class NewlyCreatedFormSubmissionSubscriber implements EventSubscriberInterface
{
    /** @var bool|null */
    protected $newlyCreated = null;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_SUBMIT => ['onFormSubmit', 0],
        ];
    }

    /**
     * Records the value of the 'newlyCreated' flag on a lead passed via
     * SubmissionEvent.
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        $this->newlyCreated = $event->getLead()->isNewlyCreated();
    }

    /**
     * Get recorded value of Lead's newlyCreated flag.
     *
     * @return bool|null
     */
    public function getNewlyCreated()
    {
        return $this->newlyCreated;
    }
}
