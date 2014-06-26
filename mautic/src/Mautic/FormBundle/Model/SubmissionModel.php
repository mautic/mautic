<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Result;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class SubmissionModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class SubmissionModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFormBundle:Submission');
    }

    /**
     * @param $post
     * @param $server
     * @param $form
     *
     * @return boolean|string false if no error was encountered; otherwise the error message
     */
    public function saveSubmission(&$post, &$server, &$form)
    {
        $fieldHelper = new FormFieldHelper($this->translator);

        //everything matches up so let's save the results
        $submission = new Submission();
        $submission->setDateSubmitted(new \DateTime());
        $submission->setForm($form);

        $ip         = $server['REMOTE_ADDR'];
        //does the IP address exist in the database?
        $ipAddress  = $this->em->getRepository('MauticCoreBundle:IpAddress')->findOneByIpAddress($ip);
        if ($ipAddress === null) {
            $ipAddress = new IpAddress();
            $ipAddress->setIpAddress($ip);
        }
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

        $fields     = $form->getFields();
        $errors     = array();
        $fieldArray = array();
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
                    if (!empty($props['errorMessage'])) {
                        $errors[] = $props['errorMessage'];
                    } else {
                        $errors = array_merge($errors, $captcha);
                    }
                }
                continue;
            }

            if ($f->isRequired() && empty($value)) {
                //somehow the user got passed the JS validation
                $msg = $f->getValidationMessage();
                if (empty($msg)) {
                    $msg = $this->get('translator')->trans('mautic.form.field.generic.validationfailed', array(
                        '%label%' => $f->getLabel()
                    ));
                }
                return $msg;
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
                    $customErrors =  call_user_func_array($params['valueConstraints'], array($f, $value));
                    if (is_array($customErrors)) {
                        $errors = array_merge($errors, $customErrors);
                    } else {
                        $errors[] = $customErrors;
                    }
                }

            } else {
                if (!empty($value)) {
                    $filter = $fieldHelper->getFieldFilter($type);
                    $value  = InputHelper::_($value, $filter);
                }
                $errors = array_merge($errors, $fieldHelper->validateFieldValue($type, $value));
            }

            //save the result
            $result = new Result();
            $result->setField($f);
            $result->setSubmission($submission);

            //convert array from checkbox groups and multiple selects
            if (is_array($value)) {
                $value = implode(", ", $value);
            }

            $result->setValue($value);

            $submission->addResult($result);
        }

        //return errors
        if (!empty($errors)) {
            return $errors;
        }

        $this->saveEntity($submission);

        //execute submit actions
        $actions = $form->getActions();

        $args = array(
            'post'     => $post,
            'server'   => $server,
            'factory'  => $this->factory,
            'feedback' => array(),
            'fields'   => $fieldArray,
            'form'     => $form
        );

        foreach ($actions as $action) {
            $args['action'] = $action;
            $key            = $action->getType();
            $settings       = $action->getSettings();
            $callback       = $settings['callback'];
            if (is_callable($callback)) {

                if (is_array($callback)){
                    $reflection = new \ReflectionMethod($callback[0], $callback[1]);
                } elseif (strpos($callback, '::') !== false) {
                    $parts = explode('::', $callback);
                    $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                } else {
                    new \ReflectionMethod(null, $callback);
                }

                $pass = array();
                foreach($reflection->getParameters() as $param) {
                    if(isset($args[$param->getName()])) {
                        $pass[] = $args[$param->getName()];
                    } else {
                        $pass[] = null;
                    }
                }
                $returned = $reflection->invokeArgs($this, $pass);
                $args['feedback'][$key] = $returned;
            }
        }

        //made it to the end so return false that there was not an error
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $args
     * @return mixed|void
     */
    public function getEntities(array $args = array())
    {
        $repo = $this->getRepository();
        $repo->setFactory($this->factory);
        return $repo->getEntities($args);
    }

    /**
     * Export results
     *
     * @param $format
     * @param $form
     * @param $queryArgs
     * @return StreamedResponse
     */
    public function exportResults($format, $form, $queryArgs)
    {
        $results = $this->getEntities($queryArgs);
        $translator = $this->translator;

        $date = $this->factory->getDate()->toLocalString();
        $name = str_replace(' ' , '_', $date) . '_' . $form->getAlias();

        switch ($format) {
            case 'csv':
                $response = new StreamedResponse(function () use ($results, $form, $translator) {
                    $handle = fopen('php://output', 'r+');

                    //build the header row
                    $fields = $form->getFields();
                    $header = array(
                        '',
                        $translator->trans('mautic.form.result.thead.date'),
                        $translator->trans('mautic.form.result.thead.ip'),
                        $translator->trans('mautic.form.result.thead.referer')
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
                            if (in_array($r['field']['type'], array('button', 'freetext')))
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
                break;
            case 'html':
                $content = $this->factory->getTemplating()->renderResponse(
                    'MauticFormBundle:Result:export.html.php',
                    array(
                        'form'  => $form,
                        'results' => $results,
                        'dateFormat' => $this->factory->getParam('date_format_full'),
                        'pageTitle'  => $name
                    )
                )->getContent();
                return new Response($content);
                break;
            case 'pdf':
                $response = new StreamedResponse(function () use ($results, $form, $translator, $name) {
                    $mpdf    = new \mPDF();
                    $content = $this->factory->getTemplating()->renderResponse(
                        'MauticFormBundle:Result:export.html.php',
                        array(
                            'form'       => $form,
                            'results'    => $results,
                            'dateFormat' => $this->factory->getParam('date_format_full'),
                            'pageTitle'  => $name
                        )
                    )->getContent();
                    $mpdf->WriteHTML($content);
                    $mpdf->Output();
                });
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $name . '.pdf"');
                $response->headers->set('Expires', 0);
                $response->headers->set('Cache-Control', 'must-revalidate');
                $response->headers->set('Pragma', 'public');
                return $response;
                break;
            case 'excel':
                if (class_exists('PHPExcel')) {
                   $response = new StreamedResponse(function () use ($results, $form, $translator, $name) {
                        $objPHPExcel = new \PHPExcel();
                        $objPHPExcel->getProperties()->setTitle($name);

                        $objPHPExcel->createSheet();

                        //build the header row
                        $fields = $form->getFields();
                        $header = array(
                            '',
                            $translator->trans('mautic.form.result.thead.date'),
                            $translator->trans('mautic.form.result.thead.ip'),
                            $translator->trans('mautic.form.result.thead.referer')
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
                                if (in_array($r['field']['type'], array('button', 'freetext')))
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
                } else {
                    throw new \Exception('PHPExcel is required to export to Excel spreadsheets');
                }
                break;
        }
    }
}