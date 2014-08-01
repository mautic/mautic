<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Response;


class PublicController extends CommonFormController
{
    public function submitAction()
    {
        $post    = $this->request->request->get('mauticform');
        $server  = $this->request->server->all();
        $return  = $post['return'];
        if (empty($return)) {
            //try to get it from the HTTP_REFERER
            $return = $server['HTTP_REFERER'];
        }
        //remove mauticError and mauticMessage from the referer so it doesn't get sent back
        $return = InputHelper::url($return, null, null, array('mauticError', 'mauticMessage'));
        $query  = (strpos($return, '?') === false) ? '?' : '&';

        $translator = $this->get('translator');

        //check to ensure there is a formid
        if (!isset($post['formid'])) {
            $error =  $translator->trans('mautic.form.submit.error.unavailable', array(), 'flashes');
        } else {
            $formModel = $this->factory->getModel('form.form');
            $form      = $formModel->getEntity($post['formid']);

            //check to see that the form was found
            if ($form === null) {
                $error = $translator->trans('mautic.form.submit.error.unavailable', array(), 'flashes');
            } else {
                //get what to do immediately after successful post
                $postAction         = $form->getPostAction();
                $postActionProperty = $form->getPostActionProperty();

               //check to ensure the form is published
                $status = $form->getPublishStatus();
                $dateFormat = $this->factory->getParameter('date_format_full');
                if ($status == 'pending') {
                    $error = $translator->trans('mautic.form.submit.error.pending', array(
                        '%date%' => $form->getPublishUp()->format($dateFormat)
                    ), 'flashes');
                } elseif ($status == 'expired') {
                    $error = $translator->trans('mautic.form.submit.error.expired', array(
                        '%date%' => $form->getPublishDown()->format($dateFormat)
                    ), 'flashes');
                } elseif ($status != 'published') {
                    $error = $translator->trans('mautic.form.submit.error.unavailable', array(), 'flashes');
                } else {
                    $errors = $this->factory->getModel('form.submission')->saveSubmission($post, $server, $form);
                    $error = ($errors) ?
                        $this->get('translator')->trans('mautic.form.submission.errors') . '<br /><ol><li>' .
                        implode("</li><li>", $errors) . '</li></ol>' : false;
                }
            }
        }

        if (!empty($error)) {
            if ($return) {
                return $this->redirect($return . $query . 'mauticError=' . rawurlencode($error));
            } else {
                $html = "<h3>$error</h3>";
            }
        } elseif ($postAction == 'redirect') {
            return $this->redirect($postActionProperty);
        } elseif ($postAction == 'return') {
            if (!empty($return)) {
                if (!empty($postActionProperty)) {
                    $return .= $query . 'mauticMessage=' . rawurlencode($postActionProperty);
                }
                return $this->redirect($return);
            } else {
                $html = "<h3>" . $this->get('translator')->trans('mautic.form.submission.thankyou') . '</h3>';
            }
        } else {
            $html = "<h3>" . $postActionProperty . '</h3>';
        }

        $response = new Response();
        $response->setContent('<html><body>'.$html.'</body></html>');
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }


    /**
     * Generates JS file for automatic form generation
     */
    public function generateAction ()
    {
        $formId = InputHelper::int($this->request->get('id'));
        $model  = $this->factory->getModel('form.form');
        $form   = $model->getEntity($formId);
        $js     = '';

        if ($form !== null) {
            $status = $form->getPublishStatus();
            if ($status == 'published') {
                $js = $form->getCachedJs();
            }
        }

        $response = new Response();
        $response->setContent($js);
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/javascript');
        return $response;
    }

}