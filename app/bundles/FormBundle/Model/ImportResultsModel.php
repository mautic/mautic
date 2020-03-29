<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Model;

use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Import\ImportDispatcher;

/**
 * Class ImportResultsModel.
 */
class ImportResultsModel
{
    /**
     * @var SubmissionModel
     */
    private $submissionModel;

    /**
     * @var ImportDispatcher
     */
    private $importDispatcher;

    /**
     * @var FormModel
     */
    private $formModel;

    /**
     * ImportResultsModel constructor.
     *
     * @param FormModel        $formModel
     * @param SubmissionModel  $submissionModel
     * @param ImportDispatcher $importDispatcher
     */
    public function __construct(
        FormModel $formModel,
        SubmissionModel $submissionModel,
        ImportDispatcher $importDispatcher
    ) {
        $this->submissionModel  = $submissionModel;
        $this->importDispatcher = $importDispatcher;
        $this->formModel        = $formModel;
    }

    public function import(
        $fields,
        $data,
        $owner = null,
        $list = null,
        $tags = null,
        $persist = true,
        LeadEventLog $eventLog = null,
        int $importId = null,
        Import $import = null
    ) {
        $importBuilderEvent  = $this->importDispatcher->dispatchBuilder($import);
        list($type, $formId) = explode('-', $importBuilderEvent->getObject());
        $form                = $this->formModel->getEntity($formId);

        $fieldData = [];
        foreach ($fields as $entityField => $importField) {
            // Prevent overwriting existing data with empty data
            if (array_key_exists($importField, $data)) {
                $fieldData[$entityField] = $data[$importField];
            }
        }
        $results = $this->submissionModel->saveSubmission(
            $fieldData,
            [],
            $form,
            $importBuilderEvent->getRequest(),
            true
        );
        if (!empty($results['errors'])) {
            throw new \Exception(implode(', ', $results['errors']));
        }

        return true;
    }
}
