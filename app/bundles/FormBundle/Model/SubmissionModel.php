<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Result;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class SubmissionModel
 */
class SubmissionModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\FormBundle\Entity\SubmissionRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFormBundle:Submission');
    }

    /**
     * @param $post
     * @param $server
     * @param Form $form
     *
     * @return boolean|string false if no error was encountered; otherwise the error message
     */
    public function saveSubmission($post, $server, Form $form)
    {
        $fieldHelper = new FormFieldHelper($this->translator);

        //everything matches up so let's save the results
        $submission = new Submission();
        $submission->setDateSubmitted(new \DateTime());
        $submission->setForm($form);

        $ipAddress = $this->factory->getIpAddress();
        $submission->setIpAddress($ipAddress);

        if (!empty($post['return'])) {
            $referer = $post['return'];
        } elseif (!empty($server['HTTP_REFERER'])) {
            $referer = $server['HTTP_REFERER'];
        } else {
            $referer = '';
        }

        //clean the referer by removing mauticError and mauticMessage
        $referer = InputHelper::url($referer, null, null, array('mauticError', 'mauticMessage'));
        $submission->setReferer($referer);

        $fields           = $form->getFields();
        $fieldArray       = array();
        $results          = array();
        $tokens           = array();
        $leadFieldMatches = array();
        $validationErrors = array();

        foreach ($fields as $f) {
            $id    = $f->getId();
            $type  = $f->getType();
            $alias = $f->getAlias();
            $value = (isset($post[$alias])) ? $post[$alias] : '';

            $fieldArray[$id] = array(
                'id'    => $id,
                'type'  => $type,
                'alias' => $alias
            );

            if (in_array($type, array('button', 'freetext'))) {
                //don't save items that don't have a value associated with it
                continue;
            } elseif ($type == 'captcha') {
                $captcha = $fieldHelper->validateFieldValue($type, $value, $f);
                if (!empty($captcha)) {
                    $props = $f->getProperties();
                    //check for a custom message
                    $validationErrors[$alias] = (!empty($props['errorMessage'])) ? $props['errorMessage'] : implode('<br />', $captcha);
                }
                continue;
            }

            if ($f->isRequired() && empty($value)) {
                //somehow the user got passed the JS validation
                $msg = $f->getValidationMessage();
                if (empty($msg)) {
                    $msg = $this->translator->trans('mautic.form.field.generic.validationfailed', array(
                        '%label%' => $f->getLabel()
                    ), 'validators');
                }

                $validationErrors[$alias] = $msg;

                continue;
            }

            //clean and validate the input
            if ($f->isCustom()) {
                $params = $f->getCustomParameters();
                if (!empty($value)) {
                    if (isset($params['valueFilter'])) {
                        if (is_string($params['inputFilter'] &&
                            method_exists('\Mautic\CoreBundle\Helper\InputHelper', $params['valueFilter']))) {
                            $value = InputHelper::_($value, $params['valueFilter']);
                        } elseif (is_callable($params['valueFilter'])) {
                            $value = call_user_func_array($params['valueFilter'], array($f, $value));
                        } else {
                            $value = InputHelper::_($value, 'clean');
                        }
                    } else {
                        $value = InputHelper::_($value, 'clean');
                    }
                }

                if (isset($params['valueConstraints']) && is_callable($params['valueConstraints'])) {
                    $customErrors = call_user_func_array($params['valueConstraints'], array($f, $value));
                    if (!empty($customErrors)) {
                        $validationErrors[$alias] = is_array($customErrors) ? implode('<br />', $customErrors) : $customErrors;
                    }
                }

            } elseif (!empty($value)) {
                $filter = $fieldHelper->getFieldFilter($type);
                $value  = InputHelper::_($value, $filter);

                $validation = $fieldHelper->validateFieldValue($type, $value);
                if (!empty($validation)) {
                    $validationErrors[$alias] = is_array($validation) ? implode('<br />', $validation) : $validation;
                }
            }

            //convert array from checkbox groups and multiple selects
            if (is_array($value)) {
                $value = implode(", ", $value);
            }

            $tokens["{formfield={$alias}}"] = $value;

            //save the result
            if ($f->getSaveResult() !== false) {
                $results[$alias] = $value;
            }

            $leadField = $f->getLeadField();
            if (!empty($leadField)) {
                $leadFieldMatches[$leadField] = $value;
            }
        }

        $submission->setResults($results);

        //execute submit actions
        $actions = $form->getActions();

        //get post submit actions to make sure it still exists
        $components       = $this->factory->getModel('form')->getCustomComponents();
        $availableActions = $components['actions'];

        $args = array(
            'post'       => $post,
            'server'     => $server,
            'factory'    => $this->factory,
            'submission' => $submission,
            'fields'     => $fieldArray,
            'form'       => $form,
            'tokens'     => $tokens
        );

        foreach ($actions as $action) {
            $key = $action->getType();
            if (!isset($availableActions[$key])) {
                continue;
            }

            $settings       = $availableActions[$key];
            $args['action'] = $action;
            $args['config'] = $action->getProperties();
            if (array_key_exists('validator', $settings)) {
                $callback = $settings['validator'];
                if (is_callable($callback)) {
                    if (is_array($callback)) {
                        $reflection = new \ReflectionMethod($callback[0], $callback[1]);
                    } elseif (strpos($callback, '::') !== false) {
                        $parts      = explode('::', $callback);
                        $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                    } else {
                        $reflection = new \ReflectionMethod(null, $callback);
                    }

                    $pass = array();
                    foreach ($reflection->getParameters() as $param) {
                        if (isset($args[$param->getName()])) {
                            $pass[] = $args[$param->getName()];
                        } else {
                            $pass[] = null;
                        }
                    }
                    list($validated, $validatedMessage) = $reflection->invokeArgs($this, $pass);
                    if (!$validated) {
                        $validationErrors[$alias] = $validatedMessage;
                    }
                }
            }
        }

        //return errors
        if (!empty($validationErrors)) {
            return array('errors' => $validationErrors);
        }

        //set the landing page the form was submitted from if applicable
        if (!empty($post['mauticpage'])) {
            $page = $this->factory->getModel('page.page')->getEntity((int)$post['mauticpage']);
            if ($page != null) {
                $submission->setPage($page);
            }
        }

        // Add a feedback parameter
        $args['feedback'] = array();

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        // Create/update lead
        if (!empty($leadFieldMatches)) {
            $this->createLeadFromSubmit($form, $leadFieldMatches);
        }

        if ($form->isStandalone()) {
            // Now handle post submission actions
            foreach ($actions as $action) {
                $key = $action->getType();
                if (!isset($availableActions[$key])) {
                    continue;
                }

                $settings       = $availableActions[$key];
                $args['action'] = $action;
                $args['config'] = $action->getProperties();

                // Set the lead each time in case an action updates it
                $args['lead'] = $leadModel->getCurrentLead();

                $callback = $settings['callback'];
                if (is_callable($callback)) {
                    if (is_array($callback)) {
                        $reflection = new \ReflectionMethod($callback[0], $callback[1]);
                    } elseif (strpos($callback, '::') !== false) {
                        $parts      = explode('::', $callback);
                        $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                    } else {
                        $reflection = new \ReflectionMethod(null, $callback);
                    }

                    $pass = array();
                    foreach ($reflection->getParameters() as $param) {
                        if (isset($args[$param->getName()])) {
                            $pass[] = $args[$param->getName()];
                        } else {
                            $pass[] = null;
                        }
                    }
                    $returned               = $reflection->invokeArgs($this, $pass);
                    $args['feedback'][$key] = $returned;
                }
            }
        }

        // Get updated lead with tracking ID
        if ($form->isInKioskMode()) {
            $lead = $leadModel->getCurrentLead();
        } else {
            list($lead, $trackingId, $generated) = $leadModel->getCurrentLead(true);

            //set tracking ID for stats purposes to determine unique hits
            $submission->setTrackingId($trackingId);
        }
        $submission->setLead($lead);

        if (!$form->isStandalone()) {
            // Find and add the lead to the associated campaigns

            /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
            $campaignModel = $this->factory->getModel('campaign');

            $campaigns = $campaignModel->getCampaignsByForm($form);
            if (!empty($campaigns)) {
                foreach ($campaigns as $campaign) {
                    $campaignModel->addLead($campaign, $lead);
                }
            }
        }

        //save entity after the form submission events are fired in case a new lead is created
        $this->saveEntity($submission);
        if ($this->dispatcher->hasListeners(FormEvents::FORM_ON_SUBMIT)) {
            $event = new SubmissionEvent($submission, $post, $server);
            $this->dispatcher->dispatch(FormEvents::FORM_ON_SUBMIT, $event);
        }

        //last round of callback commands from the submit actions; first come first serve
        foreach ($args['feedback'] as $k => $data) {
            if (!empty($data['callback'])) {
                return array('callback' => $data);
            }
        }

        //made it to the end so return false that there was not an error
        return false;
    }

    /**
     * Create/update lead from form submit
     *
     * @param       $form
     * @param array $leadFieldMatches
     *
     * @return Lead
     */
    protected function createLeadFromSubmit($form, array $leadFieldMatches)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model      = $this->factory->getModel('lead');
        $em         = $this->factory->getEntityManager();
        $logger     = $this->factory->getLogger();

        //set the mapped data
        $leadFields = $this->factory->getModel('lead.field')->getRepository()->getAliases(null, true, false);
        $inKioskMode = $form->isInKioskMode();

        if (!$inKioskMode) {
            // Default to currently tracked lead
            $lead          = $model->getCurrentLead();
            $leadId        = $lead->getId();
            $currentFields = $model->flattenFields($lead->getFields());

            $logger->debug('FORM: Not in kiosk mode so using current contact ID #' . $lead->getId());
        } else {
            // Default to a new lead in kiosk mode
            $lead = new Lead();
            $lead->setNewlyCreated(true);
            $currentFields = $leadFieldMatches;

            $leadId = null;

            $logger->debug('FORM: In kiosk mode so assuming a new contact');
        }

        $uniqueLeadFields = $this->factory->getModel('lead.field')->getUniqueIdentiferFields();

        // Closure to get data and unique fields
        $getData = function($currentFields, $uniqueOnly = false) use ($leadFields, $uniqueLeadFields) {
            $uniqueFieldsWithData = $data = array();
            foreach ($leadFields as $alias) {
                $data[$alias] = '';

                if (isset($currentFields[$alias])) {
                    $value        = $currentFields[$alias];
                    $data[$alias] = $value;

                    // make sure the value is actually there and the field is one of our uniques
                    if (!empty($value) && array_key_exists($alias, $uniqueLeadFields)) {
                        $uniqueFieldsWithData[$alias] = $value;
                    }
                }
            }

            return ($uniqueOnly) ? $uniqueFieldsWithData : array($data, $uniqueFieldsWithData);
        };

        // Closure to help search for a conflict
        $checkForIdentifierConflict = function($fieldSet1, $fieldSet2) use ($logger) {
            // Find fields in both sets
            $potentialConflicts = array_keys(
                array_intersect_key($fieldSet1, $fieldSet2)
            );

            $logger->debug('FORM: Potential conflicts ' . implode(', ', array_keys($potentialConflicts)) . ' = ' . implode(', ', $potentialConflicts));

            $conflicts = array();
            foreach ($potentialConflicts as $field) {
                if (!empty($fieldSet1[$field]) && !empty($fieldSet2[$field])) {
                    if (strtolower($fieldSet1[$field]) !== strtolower($fieldSet2[$field])) {
                        $conflicts[] = $field;
                    }
                }
            }

            return array(count($conflicts), $conflicts);
        };

        // Get data for the form submission
        list ($data, $uniqueFieldsWithData) = $getData($leadFieldMatches);
        $logger->debug('FORM: Unique fields submitted include ' . implode(', ', $uniqueFieldsWithData));

        // Check for duplicate lead
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leads */
        $leads = (!empty($uniqueFieldsWithData)) ? $em->getRepository('MauticLeadBundle:Lead')->getLeadsByUniqueFields($uniqueFieldsWithData, $leadId) : array();

        $uniqueFieldsCurrent = $getData($currentFields, true);
        if (count($leads)) {
            $logger->debug(count($leads) . ' found based on unique identifiers');

            /** @var \Mautic\LeadBundle\Entity\Lead $foundLead */
            $foundLead = $leads[0];

            $logger->debug('FORM: Testing contact ID# ' . $foundLead->getId() . ' for conflicts');

            // Check for a conflict with the currently tracked lead
            $foundLeadFields =  $model->flattenFields($foundLead->getFields());

            // Get unique identifier fields for the found lead then compare with the lead currently tracked
            $uniqueFieldsFound = $getData($foundLeadFields, true);
            list($hasConflict, $conflicts) = $checkForIdentifierConflict($uniqueFieldsFound, $uniqueFieldsCurrent);

            if ($inKioskMode || $hasConflict) {
                // Use the found lead without merging because there is some sort of conflict with unique identifiers or in kiosk mode and thus should not merge
                $lead = $foundLead;

                if ($hasConflict) {
                    $logger->debug('FORM: Conflicts found in ' . implode(', ' , $conflicts) . ' so not merging');
                } else {
                    $logger->debug('FORM: In kiosk mode so not merging');
                }

            } else {
                $logger->debug('FORM: Merging contacts ' . $lead->getId() . ' and ' . $foundLead->getId());

                // Merge the found lead with currently tracked lead
                $lead = $model->mergeLeads($lead, $foundLead);
            }

            // Update unique fields data for comparison with submitted data
            $currentFields       = $model->flattenFields($lead->getFields());
            $uniqueFieldsCurrent = $getData($currentFields, true);
        }

        if (!$inKioskMode) {
            // Check for conflicts with the submitted data and the currently tracked lead
            list($hasConflict, $conflicts) = $checkForIdentifierConflict($uniqueFieldsWithData, $uniqueFieldsCurrent);

            $logger->debug('FORM: Current unique contact fields ' . implode(', ', array_keys($uniqueFieldsCurrent)) . ' = ' . implode(', ', $uniqueFieldsCurrent));

            $logger->debug('FORM: Submitted unique contact fields ' . implode(', ', array_keys($uniqueFieldsWithData)) . ' = ' . implode(', ', $uniqueFieldsWithData));
            if ($hasConflict) {
                // There's a conflict so create a new lead
                $lead = new Lead();
                $lead->setNewlyCreated(true);

                $logger->debug('FORM: Conflicts found in ' . implode(', ' , $conflicts) . ' between current tracked contact and submitted data so assuming a new contact');
            }
        }

        //check for existing IP address
        $ipAddress = $this->factory->getIpAddress();

        //no lead was found by a mapped email field so create a new one
        if ($lead->isNewlyCreated()) {
            if (!$inKioskMode) {
                $lead->addIpAddress($ipAddress);
                $logger->debug('FORM: Associating ' . $ipAddress->getIpAddress() . ' to contact');
            }

        } elseif (!$inKioskMode) {
            $leadIpAddresses = $lead->getIpAddresses();
            if (!$leadIpAddresses->contains($ipAddress)) {
                $lead->addIpAddress($ipAddress);

                $logger->debug('FORM: Associating ' . $ipAddress->getIpAddress() . ' to contact');
            }
        }

        //set the mapped fields
        $model->setFieldValues($lead, $data, false);

        if (!empty($event)) {
            $event->setIpAddress($ipAddress);
            $lead->addPointsChangeLog($event);
        }

        // last active time
        $lead->setLastActive(new \DateTime());

        //create a new lead
        $model->saveEntity($lead, false);

        if (!$inKioskMode) {
            // Set the current lead which will generate tracking cookies
            $model->setCurrentLead($lead);
        } else {
            // Set system current lead which will still allow execution of events without generating tracking cookies
            $model->setSystemCurrentLead($lead);
        }

        return $lead;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities (array $args = array())
    {
        $repo = $this->getRepository();
        $repo->setFactory($this->factory);

        return $repo->getEntities($args);
    }

    /**
     * @param $format
     * @param $form
     * @param $queryArgs
     *
     * @return StreamedResponse|Response
     * @throws \Exception
     */
    public function exportResults($format, $form, $queryArgs)
    {
        $results    = $this->getEntities($queryArgs);
        $translator = $this->translator;

        $date = $this->factory->getDate()->toLocalString();
        $name = str_replace(' ', '_', $date) . '_' . $form->getAlias();

        switch ($format) {
            case 'csv':
                $response = new StreamedResponse(function () use ($results, $form, $translator) {
                    $handle = fopen('php://output', 'r+');

                    //build the header row
                    $fields = $form->getFields();
                    $header = array(
                        $translator->trans('mautic.core.id'),
                        $translator->trans('mautic.form.result.thead.date'),
                        $translator->trans('mautic.core.ipaddress'),
                        $translator->trans('mautic.form.result.thead.referrer')
                    );
                    foreach ($fields as $f) {
                        if (in_array($f->getType(), array('button', 'freetext')) || $f->getSaveResult() === false)
                            continue;
                        $header[] = $f->getLabel();
                    }
                    //free memory
                    unset($fields);

                    //write the row
                    fputcsv($handle, $header);

                    //build the data rows
                    foreach ($results as $k => $s) {
                        $row = array(
                            $s['id'],
                            $s['dateSubmitted']->format('Y-m-d H:m:s'),
                            $s['ipAddress']['ipAddress'],
                            $s['referer']
                        );
                        foreach ($s['results'] as $k2 => $r) {
                            if (in_array($r['type'], array('button', 'freetext')))
                                continue;
                            $row[] = $r['value'];
                            //free memory
                            unset($s['results'][$k2]);
                        }

                        fputcsv($handle, $row);

                        //free memory
                        unset($row, $results[$k]);;
                    }

                    fclose($handle);
                });

                $response->headers->set('Content-Type', 'application/force-download');
                $response->headers->set('Content-Type', 'application/octet-stream');
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $name . '.csv"');
                $response->headers->set('Expires', 0);
                $response->headers->set('Cache-Control', 'must-revalidate');
                $response->headers->set('Pragma', 'public');

                return $response;
            case 'html':
                $content = $this->factory->getTemplating()->renderResponse(
                    'MauticFormBundle:Result:export.html.php',
                    array(
                        'form'      => $form,
                        'results'   => $results,
                        'pageTitle' => $name
                    )
                )->getContent();

                return new Response($content);
            case 'xlsx':
                if (class_exists('PHPExcel')) {
                    $response = new StreamedResponse(function () use ($results, $form, $translator, $name) {
                        $objPHPExcel = new \PHPExcel();
                        $objPHPExcel->getProperties()->setTitle($name);

                        $objPHPExcel->createSheet();

                        //build the header row
                        $fields = $form->getFields();
                        $header = array(
                            $translator->trans('mautic.core.id'),
                            $translator->trans('mautic.form.result.thead.date'),
                            $translator->trans('mautic.core.ipaddress'),
                            $translator->trans('mautic.form.result.thead.referrer')
                        );
                        foreach ($fields as $f) {
                            if (in_array($f->getType(), array('button', 'freetext')) || $f->getSaveResult() === false)
                                continue;
                            $header[] = $f->getLabel();
                        }
                        //free memory
                        unset($fields);

                        //write the row
                        $objPHPExcel->getActiveSheet()->fromArray($header, NULL, 'A1');

                        //build the data rows
                        $count = 2;
                        foreach ($results as $k => $s) {
                            $row = array(
                                $s['id'],
                                $s['dateSubmitted']->format('Y-m-d H:m:s'),
                                $s['ipAddress']['ipAddress'],
                                $s['referer']
                            );
                            foreach ($s['results'] as $k2 => $r) {
                                if (in_array($r['type'], array('button', 'freetext')))
                                    continue;
                                $row[] = $r['value'];
                                //free memory
                                unset($s['results'][$k2]);
                            }

                            $objPHPExcel->getActiveSheet()->fromArray($row, NULL, "A{$count}");

                            //free memory
                            unset($row, $results[$k]);

                            //increment letter
                            $count++;
                        }

                        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                        $objWriter->setPreCalculateFormulas(false);

                        $objWriter->save('php://output');
                    });
                    $response->headers->set('Content-Type', 'application/force-download');
                    $response->headers->set('Content-Type', 'application/octet-stream');
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . $name . '.xlsx"');
                    $response->headers->set('Expires', 0);
                    $response->headers->set('Cache-Control', 'must-revalidate');
                    $response->headers->set('Pragma', 'public');

                    return $response;
                }
                throw new \Exception('PHPExcel is required to export to Excel spreadsheets');
            default:
                return new Response();
        }
    }

    /**
     * Get line chart data of submissions
     *
     * @param char     $unit   {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param string   $dateFormat
     * @param array    $filter
     * @param boolean  $canViewOthers
     *
     * @return array
     */
    public function getSubmissionsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = array(), $canViewOthers = true)
    {
        $chart     = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query     = $chart->getChartQuery($this->factory->getEntityManager()->getConnection());
        $q = $query->prepareTimeDataQuery('form_submissions', 'date_submitted', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'forms', 'f', 'f.id = t.form_id')
                ->andWhere('f.created_by = :userId')
                ->setParameter('userId', $this->factory->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->factory->getTranslator()->trans('mautic.form.submission.count'), $data);
        return $chart->render();
    }

    /**
     * Get a list of top submission referrers
     *
     * @param integer $limit
     * @param string  $dateFrom
     * @param string  $dateTo
     * @param array   $filters
     * @param boolean $canViewOthers
     *
     * @return array
     */
    public function getTopSubmissionReferrers($limit = 10, $dateFrom = null, $dateTo = null, $filters = array(), $canViewOthers = true)
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
                ->setParameter('userId', $this->factory->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_submitted');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of the most submisions per lead
     *
     * @param integer $limit
     * @param string  $dateFrom
     * @param string  $dateTo
     * @param array   $filters
     * @param boolean $canViewOthers
     *
     * @return array
     */
    public function getTopSubmitters($limit = 10, $dateFrom = null, $dateTo = null, $filters = array(), $canViewOthers = true)
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
                ->setParameter('userId', $this->factory->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_submitted');

        $results = $q->execute()->fetchAll();

        return $results;
    }
}
