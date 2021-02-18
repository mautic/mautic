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

use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    /**
     * @var FocusModel
     */
    private $model;

    public function __construct(FocusModel $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_POST_SAVE   => ['onFormPostSave', 0],
            FormEvents::FORM_POST_DELETE => ['onFormDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onFormPostSave(Events\FormEvent $event)
    {
        $form = $event->getForm();

        if ($event->isNew()) {
            return;
        }

        $foci = $this->model->getRepository()->findByForm($form->getId());

        if (empty($foci)) {
            return;
        }

        // Rebuild each focus
        /** @var \MauticPlugin\MauticFocusBundle\Entity\Focus $focus */
        foreach ($foci as $focus) {
            $focus->setCache(
                $this->model->generateJavascript($focus)
            );
        }

        $this->model->saveEntities($foci);
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onFormDelete(Events\FormEvent $event)
    {
        $form   = $event->getForm();
        $formId = $form->deletedId;
        $foci   = $this->model->getRepository()->findByForm($formId);

        if (empty($foci)) {
            return;
        }

        // Rebuild each focus
        /** @var \MauticPlugin\MauticFocusBundle\Entity\Focus $focus */
        foreach ($foci as $focus) {
            $focus->setForm(null);
            $focus->setCache(
                $this->model->generateJavascript($focus)
            );
        }

        $this->model->saveEntities($foci);
    }
}
