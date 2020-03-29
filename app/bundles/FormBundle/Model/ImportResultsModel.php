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

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Mautic\FormBundle\Crate\UploadFileCrate;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Event\ValidationEvent;
use Mautic\FormBundle\Exception\FileValidationException;
use Mautic\FormBundle\Exception\NoFileGivenException;
use Mautic\FormBundle\Exception\ValidationException;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Validator\UploadFieldValidator;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyChangeLog;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Event\ImportBuilderEvent;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Import\ImportDispatcher;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function __construct(FormModel $formModel, SubmissionModel $submissionModel, ImportDispatcher $importDispatcher)
    {
        $this->submissionModel  = $submissionModel;
        $this->importDispatcher = $importDispatcher;
        $this->formModel        = $formModel;
    }

    public function import($fields, $data, $owner = null, $list = null, $tags = null, $persist = true, LeadEventLog $eventLog = null, int $importId = null, Import $import = null)
    {
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
        $results = $this->submissionModel->saveSubmission($fieldData, [], $form, $importBuilderEvent->getRequest(), true);
        if (!empty($results['errors'])) {
            throw new \Exception(implode(', ', $results['errors']));
        }

        return true;
    }
}
