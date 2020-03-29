<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\ImportResultsModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Event\ImportBuilderEvent;
use Mautic\LeadBundle\LeadEvents;

class ImportFormsSubscriber extends CommonSubscriber
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var FormModel
     */
    private $formModel;

    /**
     * @var SubmissionModel
     */
    private $submissionModel;

    /**
     * @var ImportResultsModel
     */
    private $importResultsModel;

    /**
     * ImportFormsSubscriber constructor.
     *
     * @param FieldModel         $fieldModel
     * @param FormModel          $formModel
     * @param ImportResultsModel $importResultsModel
     */
    public function __construct(FieldModel $fieldModel, FormModel $formModel, ImportResultsModel $importResultsModel)
    {
        $this->fieldModel         = $fieldModel;
        $this->formModel          = $formModel;
        $this->importResultsModel = $importResultsModel;
    }

    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::IMPORT_BUILDER => ['importBuilder', 0],
        ];
    }

    /**
     * @param ImportBuilderEvent $event
     */
    public function importBuilder(ImportBuilderEvent $event)
    {
        if (strpos($event->getObject(), 'form-') !== false) {
            $event->setObjectFromRequest($event->getObject());
            $event->setFields($this->getFieldsToImport($event));
            $event->setActiveLink('#mautic_form_index');
            $event->setModel($this->importResultsModel);
            $event->setLabel('mautic.form.import.view_forms');
            $event->setRoute('mautic_form_index');
        }
    }

    /**
     * @param ImportBuilderEvent $event
     *
     * @return array
     */
    private function getFieldsToImport(ImportBuilderEvent $event)
    {
        list($type, $formId) = explode('-', $event->getObject());
        $form                = $this->formModel->getEntity($formId);
        $formFields          = [];
        foreach ($form->getFields() as $field) {
            $formFields[$field->getAlias()] = $field->getLabel();
        }

        return [
            'mautic.form.form' => $formFields,
        ];
    }
}
