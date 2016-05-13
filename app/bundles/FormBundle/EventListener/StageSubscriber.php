<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\StageBundle\Event\StageBuilderEvent;
use Mautic\StageBundle\StageEvents;

/**
 * Class StageSubscriber
 */
class StageSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            StageEvents::STAGE_ON_BUILD => array('onStageBuild', 0),
            FormEvents::FORM_ON_SUBMIT  => array('onFormSubmit', 0)
        );
    }

    /**
     * @param StageBuilderEvent $event
     */
    public function onStageBuild(StageBuilderEvent $event)
    {
        $action = array(
            'group'       => 'mautic.form.stage.action',
            'label'       => 'mautic.form.stage.action.submit',
            'description' => 'mautic.form.stage.action.submit_descr',
            'callback'    => array('\\Mautic\\FormBundle\\Helper\\StageActionHelper', 'validateFormSubmit'),
            'formType'    => 'stageaction_formsubmit'
        );

        $event->addAction('form.submit', $action);
    }

    /**
     * Trigger stage actions for form submit
     *
     * @param SubmissionEvent $event
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        $this->factory->getModel('stage')->triggerAction('form.submit', $event->getSubmission());
    }
}
