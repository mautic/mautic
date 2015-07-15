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

            //save the result
            $results[$alias] = $value;

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

        //set the mapped data
        $leadFields = $this->factory->getModel('lead.field')->getRepository()->getAliases(null, true, false);
        $data       = array();

        $inKioskMode = $form->isInKioskMode();

        if (!$inKioskMode) {
            $lead          = $model->getCurrentLead();
            $leadId        = $lead->getId();
            $currentFields = $lead->getFields();
        } else {
            $lead = new Lead();
            $lead->setNewlyCreated(true);

            $leadId = null;
        }

        $uniqueLeadFields     = $this->factory->getModel('lead.field')->getUniqueIdentiferFields();
        $uniqueFieldsWithData = array();

        foreach ($leadFields as $alias) {
            $data[$alias] = '';

            if (isset($leadFieldMatches[$alias])) {
                $value        = $leadFieldMatches[$alias];
                $data[$alias] = $value;

                // make sure the value is actually there and the field is one of our uniques
                if (!empty($value) && array_key_exists($alias, $uniqueLeadFields)) {
                    $uniqueFieldsWithData[$alias] = $value;
                }
            }
        }

        //update the lead rather than creating a new one if there is for sure identifier match ($leadId is to exclude lead from getCurrentLead())
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leads */
        $leads = (!empty($uniqueFieldsWithData)) ? $em->getRepository('MauticLeadBundle:Lead')->getLeadsByUniqueFields($uniqueFieldsWithData, $leadId) : array();

        if (count($leads)) {
            //merge with current lead if not in kiosk mode
            $lead = ($inKioskMode) ? $leads[0] : $model->mergeLeads($lead, $leads[0]);
        } elseif (!$inKioskMode) {
            // Flatten current fields
            $currentFields = $model->flattenFields($currentFields);

            // Create a new lead if unique identifiers differ from getCurrentLead() and submitted data
            foreach ($uniqueLeadFields as $alias => $value) {
                //create a new lead if details differ
                $currentValue = $currentFields[$alias];
                if (!empty($currentValue) && strtolower($currentValue) != strtolower($value)) {
                    //for sure a different lead so create a new one
                    $lead = new Lead();
                    $lead->setNewlyCreated(true);
                    break;
                }
            }
        }

        //check for existing IP address
        $ipAddress = $this->factory->getIpAddress();

        //no lead was found by a mapped email field so create a new one
        if ($lead->isNewlyCreated()) {
            if (!$inKioskMode) {
                $lead->addIpAddress($ipAddress);
            }

            // last active time
            $lead->setLastActive(new \DateTime());

        } elseif (!$inKioskMode) {
            $leadIpAddresses = $lead->getIpAddresses();
            if (!$leadIpAddresses->contains($ipAddress)) {
                $lead->addIpAddress($ipAddress);
            }
        }

        //set the mapped fields
        $model->setFieldValues($lead, $data, false);

        if (!empty($event)) {
            $event->setIpAddress($ipAddress);
            $lead->addPointsChangeLog($event);
        }

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
                        if (in_array($f->getType(), array('button', 'freetext')))
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
                            if (in_array($f->getType(), array('button', 'freetext')))
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
}
