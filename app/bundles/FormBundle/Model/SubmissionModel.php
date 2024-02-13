<?php

namespace Mautic\FormBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\FormBundle\Crate\UploadFileCrate;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Event\ValidationEvent;
use Mautic\FormBundle\Exception\FileValidationException;
use Mautic\FormBundle\Exception\NoFileGivenException;
use Mautic\FormBundle\Exception\ValidationException;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\ProgressiveProfiling\DisplayManager;
use Mautic\FormBundle\Validator\UploadFieldValidator;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\CustomFieldValueHelper;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Model\PageModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @extends CommonFormModel<Submission>
 */
class SubmissionModel extends CommonFormModel
{
    public function __construct(
        protected IpLookupHelper $ipLookupHelper,
        protected Environment $twig,
        protected FormModel $formModel,
        protected PageModel $pageModel,
        protected LeadModel $leadModel,
        protected CampaignModel $campaignModel,
        protected MembershipManager $membershipManager,
        protected LeadFieldModel $leadFieldModel,
        protected CompanyModel $companyModel,
        protected FormFieldHelper $fieldHelper,
        private UploadFieldValidator $uploadFieldValidator,
        private FormUploader $formUploader,
        private DeviceTrackingServiceInterface $deviceTrackingService,
        private FieldValueTransformer $fieldValueTransformer,
        private DateHelper $dateHelper,
        private ContactTracker $contactTracker,
        private ContactMerger $contactMerger,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): SubmissionRepository
    {
        return $this->em->getRepository(Submission::class);
    }

    /**
     * @param bool $returnEvent
     *
     * @return bool|array
     *
     * @throws ORMException
     */
    public function saveSubmission($post, $server, Form $form, Request $request, $returnEvent = false)
    {
        $leadFields = $this->leadFieldModel->getFieldListWithProperties(false);

        // everything matches up so let's save the results
        $submission = new Submission();
        $submission->setDateSubmitted(new \DateTime());
        $submission->setForm($form);

        // set the landing page the form was submitted from if applicable
        if (!empty($post['mauticpage'])) {
            $page = $this->pageModel->getEntity((int) $post['mauticpage']);
            if (null != $page) {
                $submission->setPage($page);
            }
        }

        $ipAddress = $this->ipLookupHelper->getIpAddress();
        $submission->setIpAddress($ipAddress);

        if (!empty($post['return'])) {
            $referer = $post['return'];
        } elseif (!empty($server['HTTP_REFERER'])) {
            $referer = $server['HTTP_REFERER'];
        } else {
            $referer = '';
        }

        // clean the referer by removing mauticError and mauticMessage
        $referer = InputHelper::url($referer, null, null, ['mauticError', 'mauticMessage']);
        $submission->setReferer($referer);

        // Create an event to be dispatched through the processes
        $submissionEvent = new SubmissionEvent($submission, $post, $server, $request);

        // Get a list of components to build custom fields from
        $components = $this->formModel->getCustomComponents();

        $fields           = $form->getFields();
        $fieldArray       = [];
        $results          = [];
        $tokens           = [];
        $leadFieldMatches = [];
        $validationErrors = [];
        $filesToUpload    = new UploadFileCrate();

        /** @var Field $f */
        foreach ($fields as $f) {
            $id    = $f->getId();
            $type  = $f->getType();
            $alias = $f->getAlias();
            $value = $post[$alias] ?? '';

            $fieldArray[$id] = [
                'id'    => $id,
                'type'  => $type,
                'alias' => $alias,
            ];

            if ($f->isCaptchaType()) {
                $captcha = $this->fieldHelper->validateFieldValue($type, $value, $f);
                if (!empty($captcha)) {
                    $props = $f->getProperties();
                    // check for a custom message
                    $validationErrors[$alias] = (!empty($props['errorMessage'])) ? $props['errorMessage'] : implode('<br />', $captcha);
                }
                continue;
            } elseif ($f->isFileType()) {
                try {
                    $file  = $this->uploadFieldValidator->processFileValidation($f, $request);
                    $value = $file->getClientOriginalName();
                    $filesToUpload->addFile($file, $f);
                } catch (NoFileGivenException) { // No error here, we just move to another validation, eg. if a field is required
                } catch (FileValidationException $e) {
                    $validationErrors[$alias] = $e->getMessage();
                }
            }

            if (!$f->showForConditionalField($post)) {
                continue;
            }

            if ('' === $value && $f->isRequired()) {
                // field is required, but hidden from form because of 'ShowWhenValueExists'
                if (false === $f->getShowWhenValueExists() && !isset($post[$alias])) {
                    continue;
                }

                // somehow the user got passed the JS validation
                $msg = $f->getValidationMessage();
                if (empty($msg)) {
                    $msg = $this->translator->trans(
                        'mautic.form.field.generic.validationfailed',
                        [
                            '%label%' => $f->getLabel(),
                        ],
                        'validators'
                    );
                }

                $validationErrors[$alias] = $msg;

                continue;
            }

            if (isset($components['viewOnlyFields']) && in_array($type, $components['viewOnlyFields'])) {
                // don't save items that don't have a value associated with it
                continue;
            }

            // clean and validate the input
            if ($f->isCustom()) {
                if (!isset($components['fields'][$f->getType()])) {
                    continue;
                }

                $params = $components['fields'][$f->getType()];
                if (!empty($value)) {
                    if (isset($params['valueFilter'])) {
                        if (is_string($params['valueFilter']) && is_callable([\Mautic\CoreBundle\Helper\InputHelper::class, $params['valueFilter']])) {
                            $value = InputHelper::_($value, $params['valueFilter']);
                        } elseif (is_callable($params['valueFilter'])) {
                            $value = call_user_func_array($params['valueFilter'], [$f, $value]);
                        } else {
                            $value = InputHelper::_($value, 'clean');
                        }
                    } else {
                        $value = InputHelper::_($value, 'clean');
                    }
                }
            } elseif (!empty($value)) {
                $filter = $this->fieldHelper->getFieldFilter($type);
                $value  = InputHelper::_($value, $filter);

                $isValid = $this->validateFieldValue($f, $value);
                if (true !== $isValid) {
                    $validationErrors[$alias] = is_array($isValid) ? implode('<br />', $isValid) : $isValid;
                }
            }

            // Check for custom validators
            $isValid = $this->validateFieldValue($f, $value);
            if (true !== $isValid) {
                $validationErrors[$alias] = $isValid;
            }

            $mappedField = $f->getMappedField();
            if (!empty($mappedField) && in_array($f->getMappedObject(), ['company', 'contact'])) {
                $leadValue = $value;

                $leadFieldMatches[$mappedField] = $leadValue;
            }

            $tokens["{formfield={$alias}}"] = $this->normalizeValue($value, $f);

            // convert array from checkbox groups and multiple selects
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // save the result
            if (false !== $f->getSaveResult()) {
                $results[$alias] = $value;
            }
        }

        // Set the results
        $submission->setResults($results);

        // Update the event
        $submissionEvent->setFields($fieldArray)
            ->setTokens($tokens)
            ->setResults($results)
            ->setContactFieldMatches($leadFieldMatches);

        $lead = $this->contactTracker->getContact();

        // Remove validation errors if the field is not visible
        if ($lead && $form->usesProgressiveProfiling()) {
            $leadSubmissions = $this->formModel->getLeadSubmissions($form, $lead->getId());

            $displayManager = new DisplayManager($form, $this->formModel->getCustomComponents()['viewOnlyFields']);
            foreach ($fields as $field) {
                if ($field->showForContact($leadSubmissions, $lead, $form, $displayManager)) {
                    $displayManager->increaseDisplayedFields($field);
                } elseif (isset($validationErrors[$field->getAlias()])) {
                    unset($validationErrors[$field->getAlias()]);
                }
            }
        }

        // return errors if there any
        if (!empty($validationErrors)) {
            return ['errors' => $validationErrors];
        }

        // Create/update lead
        if (!empty($leadFieldMatches)) {
            $lead = $this->createLeadFromSubmit($form, $leadFieldMatches, $leadFields);
        }

        $trackedDevice = $this->deviceTrackingService->getTrackedDevice();
        $trackingId    = (null === $trackedDevice ? null : $trackedDevice->getTrackingId());

        // set tracking ID for stats purposes to determine unique hits
        $submission->setTrackingId($trackingId)
            ->setLead($lead);

        /*
         * Process File upload and save the result to the entity
         * Upload is here to minimize a need for deleting file if there is a validation error
         * The action can still be invalidated below - deleteEntity takes care for File deletion
         *
         * @todo Refactor form validation to execute this code only if Submission is valid
         */
        try {
            $this->formUploader->uploadFiles($filesToUpload, $submission);
        } catch (FileUploadException $e) {
            $msg                                = $this->translator->trans('mautic.form.submission.error.file.uploadFailed', [], 'validators');
            $validationErrors[$e->getMessage()] = $msg;

            return ['errors' => $validationErrors];
        }

        // set results after uploader what can change file name if file name exists
        $submissionEvent->setResults($submission->getResults());

        // Save the submission
        $this->saveEntity($submission);
        $this->fieldValueTransformer->transformValuesAfterSubmit($submissionEvent);
        // Now handle post submission actions
        try {
            $this->executeFormActions($submissionEvent);
        } catch (ValidationException $exception) {
            // The action invalidated the form for whatever reason
            $this->deleteEntity($submission);

            if ($validationErrors = $exception->getViolations()) {
                return ['errors' => $validationErrors];
            }

            return ['errors' => [$exception->getMessage()]];
        }

        // update contact fields with transform values
        if (!empty($this->fieldValueTransformer->getContactFieldsToUpdate())) {
            $this->leadModel->setFieldValues($lead, $this->fieldValueTransformer->getContactFieldsToUpdate());
            $this->leadModel->saveEntity($lead, false);
        }

        if (!$form->isStandalone()) {
            // Find and add the lead to the associated campaigns
            $campaigns = $this->campaignModel->getCampaignsByForm($form);
            /** @var Campaign $campaign */
            foreach ($campaigns as $campaign) {
                if ($campaign->isPublished()) {
                    $this->membershipManager->addContact($lead, $campaign);
                }
            }
        }

        if ($this->dispatcher->hasListeners(FormEvents::FORM_ON_SUBMIT)) {
            // Reset action config from executeFormActions()
            $submissionEvent->setAction(null);

            // Dispatch to on submit listeners
            $this->dispatcher->dispatch($submissionEvent, FormEvents::FORM_ON_SUBMIT);
        }

        // get callback commands from the submit action
        if ($submissionEvent->hasPostSubmitCallbacks()) {
            return ['callback' => $submissionEvent];
        }

        // made it to the end so return the submission event to give the calling method access to tokens, results, etc
        // otherwise return false that no errors were encountered (to keep BC really)
        return ($returnEvent) ? ['submission' => $submissionEvent] : false;
    }

    /**
     * @param Submission $submission
     */
    public function deleteEntity($submission): void
    {
        $this->formUploader->deleteUploadedFiles($submission);

        parent::deleteEntity($submission);
    }

    public function getEntities(array $args = [])
    {
        return $this->getRepository()->getEntities($args);
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<mixed>
     */
    public function getEntitiesByPage(array $args = []): array
    {
        return $this->getRepository()->getEntitiesByPage($args);
    }

    /**
     * @return StreamedResponse|Response
     *
     * @throws \Exception
     */
    public function exportResults($format, $form, $queryArgs)
    {
        $viewOnlyFields              = $this->formModel->getCustomComponents()['viewOnlyFields'];
        $queryArgs['viewOnlyFields'] = $viewOnlyFields;
        $queryArgs['simpleResults']  = true;
        $results                     = $this->getEntities($queryArgs);

        $date = (new DateTimeHelper())->toLocalString();
        $name = str_replace(' ', '_', $date).'_'.$form->getAlias();

        switch ($format) {
            case 'csv':
                $response = new StreamedResponse(
                    function () use ($results, $form, $viewOnlyFields): void {
                        $handle = fopen('php://output', 'r+');

                        // build the header row
                        $header = $this->getExportHeader($form, $viewOnlyFields);

                        // write the row
                        $this->putCsvExportRow($handle, $header);

                        // build the data rows
                        foreach ($results as $k => $s) {
                            $row = $this->getExportRow($s, $viewOnlyFields);
                            $this->putCsvExportRow($handle, $row);

                            // free memory
                            unset($row, $results[$k]);
                        }

                        fclose($handle);
                    }
                );

                $this->setResponseHeaders($response, $name.'.csv', [
                    'application/force-download',
                    'application/octet-stream',
                ]);

                return $response;
            case 'html':
                $content = $this->twig->render(
                    '@MauticForm/Result/export.html.twig',
                    [
                        'form'           => $form,
                        'results'        => $results,
                        'pageTitle'      => $name,
                        'viewOnlyFields' => $viewOnlyFields,
                    ]
                );

                return new Response($content);
            case 'xlsx':
                if (class_exists(Spreadsheet::class)) {
                    $response = new StreamedResponse(
                        function () use ($results, $form, $name, $viewOnlyFields): void {
                            $objPHPExcel = new Spreadsheet();
                            $objPHPExcel->getProperties()->setTitle($name);

                            $objPHPExcel->createSheet();

                            // build the header row
                            $header = $this->getExportHeader($form, $viewOnlyFields);

                            // write the row
                            $objPHPExcel->getActiveSheet()->fromArray($header, null, 'A1');

                            // build the data rows
                            $count = 2;
                            foreach ($results as $k => $s) {
                                $row = $this->getExportRow($s, $viewOnlyFields);

                                $objPHPExcel->getActiveSheet()->fromArray($row, null, "A{$count}");

                                // free memory
                                unset($row, $results[$k]);

                                // increment letter
                                ++$count;
                            }

                            $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
                            $objWriter->setPreCalculateFormulas(false);

                            $objWriter->save('php://output');
                        }
                    );

                    $this->setResponseHeaders($response, $name.'.xlsx', [
                        'application/force-download',
                        'application/octet-stream',
                    ]);

                    return $response;
                }
                throw new \Exception('PHPSpreadsheet is required to export to Excel spreadsheets');
            default:
                return new Response();
        }
    }

    /**
     * @param string               $format
     * @param object               $page
     * @param array<string, mixed> $queryArgs
     *
     * @return StreamedResponse|Response
     *
     * @throws \Exception
     */
    public function exportResultsForPage($format, $page, $queryArgs)
    {
        $results    = $this->getEntitiesByPage($queryArgs);
        $results    = $results['results'];

        $date = (new DateTimeHelper())->toLocalString();
        $name = str_replace(' ', '_', $date).'_'.$page->getAlias();

        switch ($format) {
            case 'csv':
                $response = new StreamedResponse(
                    function () use ($results): void {
                        $handle = fopen('php://output', 'r+');

                        // build the header row
                        $header = $this->getExportHeaderForPage();
                        $this->putCsvExportRow($handle, $header);

                        // build the data rows
                        foreach ($results as $k => $s) {
                            $row = $this->getExportRowForPage($s);
                            $this->putCsvExportRow($handle, $row);

                            // free memory
                            unset($row, $results[$k]);
                        }

                        fclose($handle);
                    }
                );
                $this->setResponseHeaders($response, $name.'.csv', [
                    'text/csv; charset=UTF-8',
                ]);

                return $response;
            case 'html':
                $content = $this->twig->render(
                    '@MauticPage/Result/export.html.twig',
                    [
                        'page'      => $page,
                        'results'   => $results,
                        'pageTitle' => $name,
                    ]
                );

                return new Response($content);
            case 'xlsx':
                if (!class_exists(Spreadsheet::class)) {
                    throw new \Exception('PHPSpreadsheet is required to export to Excel spreadsheets');
                }
                $response = new StreamedResponse(
                    function () use ($results, $name): void {
                        $objPHPExcel = new Spreadsheet();
                        $objPHPExcel->getProperties()->setTitle($name);

                        $objPHPExcel->createSheet();
                        $header = $this->getExportHeaderForPage('xlsx');

                        // write the row
                        $objPHPExcel->getActiveSheet()->fromArray($header, null, 'A1');

                        // build the data rows
                        $count = 2;
                        foreach ($results as $k => $s) {
                            $row = $this->getExportRowForPage($s, 'xlsx');
                            $objPHPExcel->getActiveSheet()->fromArray($row, null, "A{$count}");

                            // free memory
                            unset($row, $results[$k]);

                            // increment letter
                            ++$count;
                        }

                        $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
                        $objWriter->setPreCalculateFormulas(false);

                        $objWriter->save('php://output');
                    }
                );
                $this->setResponseHeaders($response, $name.'.xlsx', [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);

                return $response;
            default:
                return new Response();
        }
    }

    /**
     * @param array<string> $contentType
     */
    private function setResponseHeaders(StreamedResponse $response, string $filename, array $contentType): void
    {
        foreach ($contentType as $ct) {
            $response->headers->set('Content-Type', $ct);
        }

        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');
    }

    /**
     * @param resource     $handle
     * @param array<mixed> $row
     */
    private function putCsvExportRow($handle, array $row): bool|int
    {
        return fputcsv($handle, $row);
    }

    /**
     * @param array<mixed> $values
     *
     * @return array<mixed>
     */
    private function getExportRowForPage(array $values, string $format = 'csv'): array
    {
        $row = [
            $values['id'],
            $values['leadId'],
            $this->dateHelper->toFull($values['dateSubmitted'], 'UTC'),
            $values['ipAddress'],
            $values['referer'],
        ];

        if ('csv' === $format) {
            array_splice($row, 2, 0, $values['formId']);
        }

        return $row;
    }

    /**
     * @param array<mixed> $values
     * @param array<mixed> $viewOnlyFields
     *
     * @return array<mixed>
     */
    private function getExportRow(array $values, array $viewOnlyFields = []): array
    {
        $row = [
            $values['id'],
            $values['leadId'],
            $this->dateHelper->toFull($values['dateSubmitted'], 'UTC'),
            $values['ipAddress'],
            $values['referer'],
        ];

        foreach ($values['results'] as $k2 => $r) {
            if (in_array($r['type'], $viewOnlyFields)) {
                continue;
            }

            $row[] = htmlspecialchars_decode($r['value'], ENT_QUOTES);
            // free memory
            unset($values['results'][$k2]);
        }

        return $row;
    }

    /**
     * @return array<string>
     */
    private function getExportHeaderForPage(string $format = 'csv'): array
    {
        $header = [
            $this->translator->trans('mautic.form.report.submission.id'),
            $this->translator->trans('mautic.lead.report.contact_id'),
            $this->translator->trans('mautic.form.result.thead.date'),
            $this->translator->trans('mautic.core.ipaddress'),
            $this->translator->trans('mautic.form.result.thead.referrer'),
        ];

        if ('csv' === $format) {
            array_splice($header, 2, 0, $this->translator->trans('mautic.form.report.form_id'));
        }

        return $header;
    }

    /**
     * @param array<mixed> $viewOnlyFields
     *
     * @return array<string>
     */
    private function getExportHeader(Form $form, $viewOnlyFields): array
    {
        $fields = $form->getFields();

        $header = [
            $this->translator->trans('mautic.form.report.submission.id'),
            $this->translator->trans('mautic.lead.report.contact_id'),
            $this->translator->trans('mautic.form.result.thead.date'),
            $this->translator->trans('mautic.core.ipaddress'),
            $this->translator->trans('mautic.form.result.thead.referrer'),
        ];

        foreach ($fields as $f) {
            if (in_array($f->getType(), $viewOnlyFields) || false === $f->getSaveResult()) {
                continue;
            }
            $header[] = $f->getLabel();
        }

        return $header;
    }

    /**
     * Get line chart data of submissions.
     *
     * @param string|null $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string      $dateFormat
     * @param array       $filter
     * @param bool        $canViewOthers
     */
    public function getSubmissionsLineChartData(
        ?string $unit,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        $dateFormat = null,
        $filter = [],
        $canViewOthers = true
    ): array {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('form_submissions', 'date_submitted', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'forms', 'f', 'f.id = t.form_id')
                ->andWhere('f.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.form.submission.count'), $data);

        return $chart->render();
    }

    /**
     * Get a list of top submission referrers.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     *
     * @return array
     */
    public function getTopSubmissionReferrers($limit = 10, $dateFrom = null, $dateTo = null, $filters = [], $canViewOthers = true)
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(DISTINCT t.id) AS submissions, t.referer')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 't')
            ->orderBy('submissions', 'DESC')
            ->groupBy('t.referer')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'forms', 'f', 'f.id = t.form_id')
                ->andWhere('f.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_submitted');

        return $q->execute()->fetchAllAssociative();
    }

    /**
     * Get a list of the most submisions per lead.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     *
     * @return array
     */
    public function getTopSubmitters($limit = 10, $dateFrom = null, $dateTo = null, $filters = [], $canViewOthers = true)
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(DISTINCT t.id) AS submissions, t.lead_id, l.firstname, l.lastname, l.email')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
            ->orderBy('submissions', 'DESC')
            ->groupBy('t.lead_id, l.firstname, l.lastname, l.email')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'forms', 'f', 'f.id = t.form_id')
                ->andWhere('f.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_submitted');

        return $q->execute()->fetchAllAssociative();
    }

    /**
     * Execute a form submit action.
     *
     * @throws ValidationException
     */
    protected function executeFormActions(SubmissionEvent $event): void
    {
        $actions          = $event->getSubmission()->getForm()->getActions();
        $customComponents = $this->formModel->getCustomComponents();
        $availableActions = $customComponents['actions'] ?? [];

        $actions->filter(fn (Action $action): bool => array_key_exists($action->getType(), $availableActions))->map(function (Action $action) use ($event, $availableActions): void {
            $event->setAction($action);
            $this->dispatcher->dispatch($event, $availableActions[$action->getType()]['eventName']);
        });
    }

    /**
     * Create/update lead from form submit.
     *
     * @throws ORMException
     */
    protected function createLeadFromSubmit(Form $form, array $leadFieldMatches, $leadFields): Lead
    {
        // set the mapped data
        $inKioskMode   = $form->isInKioskMode();
        $leadId        = null;
        $lead          = new Lead();
        $currentFields = $leadFieldMatches;
        $companyFields = $this->leadFieldModel->getFieldListWithProperties('company');

        if (!$inKioskMode) {
            // Default to currently tracked lead
            if ($currentLead = $this->contactTracker->getContact()) {
                $lead          = $currentLead;
                $leadId        = $lead->getId();
                $currentFields = $lead->getProfileFields();
            }

            $this->logger->debug('FORM: Not in kiosk mode so using current contact ID #'.$leadId);
        } else {
            // Default to a new lead in kiosk mode
            $lead->setNewlyCreated(true);

            $this->logger->debug('FORM: In kiosk mode so assuming a new contact');
        }

        $uniqueLeadFields = $this->leadFieldModel->getUniqueIdentifierFields();

        // Closure to get data and unique fields
        $getData = function ($currentFields, $uniqueOnly = false) use ($leadFields, $uniqueLeadFields): array {
            $uniqueFieldsWithData = $data = [];
            foreach ($leadFields as $alias => $properties) {
                if (isset($currentFields[$alias])) {
                    $value        = $currentFields[$alias];
                    $data[$alias] = $value;

                    // make sure the value is actually there and the field is one of our uniques
                    if (!empty($value) && array_key_exists($alias, $uniqueLeadFields)) {
                        $uniqueFieldsWithData[$alias] = $value;
                    }
                }
            }

            return ($uniqueOnly) ? $uniqueFieldsWithData : [$data, $uniqueFieldsWithData];
        };

        // Closure to get data and unique fields
        $getCompanyData = function ($currentFields) use ($companyFields): array {
            $companyData = [];
            // force add company contact field to company fields check
            $companyFields = array_merge($companyFields, ['company'=> 'company']);
            foreach ($companyFields as $alias => $properties) {
                if (isset($currentFields[$alias])) {
                    $value               = $currentFields[$alias];
                    $companyData[$alias] = $value;
                }
            }

            return $companyData;
        };

        // Closure to help search for a conflict
        $checkForIdentifierConflict = function ($fieldSet1, $fieldSet2): array {
            // Find fields in both sets
            $potentialConflicts = array_keys(
                array_intersect_key($fieldSet1, $fieldSet2)
            );

            $this->logger->debug(
                'FORM: Potential conflicts '.implode(', ', array_keys($potentialConflicts)).' = '.implode(', ', $potentialConflicts)
            );

            $conflicts = [];
            foreach ($potentialConflicts as $field) {
                if (!empty($fieldSet1[$field]) && !empty($fieldSet2[$field])) {
                    if (strtolower($fieldSet1[$field]) !== strtolower($fieldSet2[$field])) {
                        $conflicts[] = $field;
                    }
                }
            }

            return [count($conflicts), $conflicts];
        };

        // Get data for the form submission
        [$data, $uniqueFieldsWithData] = $getData($leadFieldMatches);
        $this->logger->debug('FORM: Unique fields submitted include '.implode(', ', $uniqueFieldsWithData));

        // Check for duplicate lead
        /** @var \Mautic\LeadBundle\Entity\Lead[] $leads */
        $leads = (!empty($uniqueFieldsWithData)) ? $this->em->getRepository(\Mautic\LeadBundle\Entity\Lead::class)->getLeadsByUniqueFields(
            $uniqueFieldsWithData,
            $leadId
        ) : [];

        $uniqueFieldsCurrent = $getData($currentFields, true);
        if (count($leads)) {
            $this->logger->debug(count($leads).' found based on unique identifiers');

            /** @var \Mautic\LeadBundle\Entity\Lead $foundLead */
            $foundLead = $leads[0];

            $this->logger->debug('FORM: Testing contact ID# '.$foundLead->getId().' for conflicts');

            // Check for a conflict with the currently tracked lead
            $foundLeadFields = $foundLead->getProfileFields();

            // Get unique identifier fields for the found lead then compare with the lead currently tracked
            $uniqueFieldsFound             = $getData($foundLeadFields, true);
            [$hasConflict, $conflicts]     = $checkForIdentifierConflict($uniqueFieldsFound, $uniqueFieldsCurrent);

            if ($inKioskMode || $hasConflict || !$lead->getId()) {
                // Use the found lead without merging because there is some sort of conflict with unique identifiers or in kiosk mode and thus should not merge
                $lead = $foundLead;

                if ($hasConflict) {
                    $this->logger->debug('FORM: Conflicts found in '.implode(', ', $conflicts).' so not merging');
                } else {
                    $this->logger->debug('FORM: In kiosk mode so not merging');
                }
            } else {
                $this->logger->debug('FORM: Merging contacts '.$lead->getId().' and '.$foundLead->getId());

                // Merge the found lead with currently tracked lead
                try {
                    $lead = $this->contactMerger->merge($lead, $foundLead);
                } catch (SameContactException) {
                }
            }

            // Update unique fields data for comparison with submitted data
            $currentFields       = $lead->getProfileFields();
            $uniqueFieldsCurrent = $getData($currentFields, true);
        }

        if (!$inKioskMode) {
            // Check for conflicts with the submitted data and the currently tracked lead
            [$hasConflict, $conflicts] = $checkForIdentifierConflict($uniqueFieldsWithData, $uniqueFieldsCurrent);

            $this->logger->debug(
                'FORM: Current unique contact fields '.implode(', ', array_keys($uniqueFieldsCurrent)).' = '.implode(', ', $uniqueFieldsCurrent)
            );

            $this->logger->debug(
                'FORM: Submitted unique contact fields '.implode(', ', array_keys($uniqueFieldsWithData)).' = '.implode(', ', $uniqueFieldsWithData)
            );
            if ($hasConflict) {
                // There's a conflict so create a new lead
                $lead = new Lead();
                $lead->setNewlyCreated(true);

                $this->logger->debug(
                    'FORM: Conflicts found in '.implode(', ', $conflicts)
                    .' between current tracked contact and submitted data so assuming a new contact'
                );
            }
        }

        // check for existing IP address
        $ipAddress = $this->ipLookupHelper->getIpAddress();

        // no lead was found by a mapped email field so create a new one
        if ($lead->isNewlyCreated()) {
            if (!$inKioskMode) {
                $lead->addIpAddress($ipAddress);
                $this->logger->debug('FORM: Associating '.$ipAddress->getIpAddress().' to contact');
            }
        } elseif (!$inKioskMode) {
            $leadIpAddresses = $lead->getIpAddresses();
            if (!$leadIpAddresses->contains($ipAddress)) {
                $lead->addIpAddress($ipAddress);

                $this->logger->debug('FORM: Associating '.$ipAddress->getIpAddress().' to contact');
            }
        }

        // set the mapped fields
        $this->leadModel->setFieldValues($lead, $data, false, true, true);

        // last active time
        $lead->setLastActive(new \DateTime());

        // create a new lead
        $lead->setManipulator(new LeadManipulator(
            'form',
            'submission',
            $form->getId(),
            $form->getName()
        ));
        $this->leadModel->saveEntity($lead, false);

        if (!$inKioskMode) {
            // Set the current lead which will generate tracking cookies
            $this->contactTracker->setTrackedContact($lead);
        } else {
            // Set system current lead which will still allow execution of events without generating tracking cookies
            $this->contactTracker->setSystemContact($lead);
        }

        $companyFieldMatches = $getCompanyData($leadFieldMatches);
        if (!empty($companyFieldMatches)) {
            [$company, $leadAdded, $companyEntity] = IdentifyCompanyHelper::identifyLeadsCompany($companyFieldMatches, $lead, $this->companyModel);
            $companyChangeLog                      = null;
            if ($leadAdded) {
                $companyChangeLog = $lead->addCompanyChangeLogEntry('form', 'Identify Company', 'Lead added to the company, '.$company['companyname'], $company['id']);
            } elseif ($companyEntity instanceof Company) {
                $this->companyModel->setFieldValues($companyEntity, $companyFieldMatches);
                $this->companyModel->saveEntity($companyEntity);
            }

            if (!empty($company) and $companyEntity instanceof Company) {
                // Save after the lead in for new leads created through the API and maybe other places
                $this->companyModel->addLeadToCompany($companyEntity, $lead);
                $this->leadModel->setPrimaryCompany($companyEntity->getId(), $lead->getId());
            }
            if (null !== $companyChangeLog) {
                $this->companyModel->getCompanyLeadRepository()->detachEntity($companyChangeLog);
            }
        }

        return $lead;
    }

    /**
     * Validates a field value.
     *
     * @return bool|string True if valid; otherwise string with invalid reason
     */
    protected function validateFieldValue(Field $field, $value)
    {
        $standardValidation = $this->fieldHelper->validateFieldValue($field->getType(), $value, $field);
        if (!empty($standardValidation)) {
            return $standardValidation;
        }

        $components = $this->formModel->getCustomComponents();
        foreach ([$field->getType(), 'form'] as $type) {
            if (isset($components['validators'][$type])) {
                if (!is_array($components['validators'][$type])) {
                    $components['validators'][$type] = [$components['validators'][$type]];
                }
                foreach ($components['validators'][$type] as $validator) {
                    if (!is_array($validator)) {
                        $validator = ['eventName' => $validator];
                    }
                    $event = $this->dispatcher->dispatch(new ValidationEvent($field, $value), $validator['eventName']);
                    if (!$event->isValid()) {
                        return $event->getInvalidReason();
                    }
                }
            }
        }

        return true;
    }

    private function normalizeValue($value, Field $f): string
    {
        $value = !is_array($value) ? [$value] : $value;

        // select and multiselect normalization
        if ($properties = $f->getProperties()['list'] ?? null) {
            foreach ($value as $key => $item) {
                $value[$key] = CustomFieldValueHelper::setValueFromPropertiesList($properties, $item);
            }
        }

        return implode(', ', $value);
    }
}
