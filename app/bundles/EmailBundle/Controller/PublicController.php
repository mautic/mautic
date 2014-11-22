<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PublicController extends CommonFormController
{
    public function indexAction($idHash)
    {
        //find the email
        $security   = $this->factory->getSecurity();
        $model      = $this->factory->getModel('email');
        $translator = $this->get('translator');
        $stat       = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $entity   = $stat->getEmail();
            $statLead = $stat->getLead();

            //the lead needs to have fields populated
            $lead = $this->factory->getModel('lead')->getLead($statLead->getId());

            $published = $entity->isPublished();

            //make sure the page is published or deny access if not
            if ((!$published) && (!$security->hasEntityAccess(
                    'email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy()))
            ) {
                throw new AccessDeniedHttpException($translator->trans('mautic.core.url.error.401'));
            }

            //all the checks pass so display the content
            $model->hitEmail($entity, $idHash, $this->request, true);

            if ($entity->getContentMode() == 'builder') {
                $template   = $entity->getTemplate();
                $slots      = $this->factory->getTheme($template)->getSlots('email');

                $response = $this->render('MauticEmailBundle::public.html.php', array(
                    'inBrowser' => true,
                    'slots'     => $slots,
                    'content'   => $entity->getContent(),
                    'email'     => $entity,
                    'lead'      => $lead,
                    'template'  => $template,
                    'idHash'    => $idHash
                ));

                //replace tokens
                $content = $response->getContent();
            } else {
                $content = $entity->getCustomHtml();
            }

            $dispatcher = $this->get('event_dispatcher');
            if ($dispatcher->hasListeners(EmailEvents::EMAIL_ON_DISPLAY)) {
                $event = new EmailSendEvent($content, $entity, $lead, $idHash);
                $dispatcher->dispatch(EmailEvents::EMAIL_ON_DISPLAY, $event);
                $content = $event->getContent();
            }

            return new Response($content);
        }

        throw $this->createNotFoundException($translator->trans('mautic.core.url.error.404'));
    }

    /**
     * @param $idHash
     *
     * @return Response
     */
    public function trackingImageAction($idHash)
    {
        $response = TrackingPixelHelper::getResponse($this->request);

        $model    = $this->factory->getModel('email');
        $stat     = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $entity = $stat->getEmail();
            if ($entity !== null) {
                $entity->setReadInBrowser(true);
                $model->hitEmail($entity, $idHash, $this->request);
            }
        }

        //generate image
        return $response;
    }

    /**
     * @param $idHash
     */
    public function unsubscribeAction($idHash)
    {
        //find the email
        $model      = $this->factory->getModel('email');
        $translator = $this->get('translator');
        $stat       = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $email = $stat->getEmail();
            $lead  = $stat->getLead();

            $template = $email->getTemplate();

            $model->setDoNotContact($stat, $translator->trans('mautic.email.dnc.unsubscribed'), 'unsubscribed');

            $message = $translator->trans('mautic.email.unsubscribed.success', array(
                '%email%' => $stat->getEmailAddress(),
                '%resubscribeUrl%' => $this->generateUrl('mautic_email_resubscribe', array('idHash' => $idHash))
            ));
        } else {
            $email = $lead = false;
        }

        return $this->render('MauticEmailBundle::message.html.php', array(
            'message'  => $message,
            'type'     => 'notice',
            'email'    => $email,
            'lead'     => $lead,
            'template' => $template
        ));
    }

    /**
     * @param $idHash
     */
    public function resubscribeAction($idHash)
    {
        //find the email
        $model      = $this->factory->getModel('email');
        $translator = $this->get('translator');
        $stat       = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $email = $stat->getEmail();
            $lead  = $stat->getLead();

            $template = $email->getTemplate();

            $model->removeDoNotContact($stat->getEmailAddress());

            $message = $translator->trans('mautic.email.resubscribed.success', array(
                '%email%' => $stat->getEmailAddress(),
                '%unsubscribeUrl%' => $this->generateUrl('mautic_email_unsubscribe', array('idHash' => $idHash))
            ));
        } else {
            $email = $lead = false;
        }

        return $this->render('MauticEmailBundle::message.html.php', array(
            'message'  => $message,
            'type'     => 'notice',
            'email'    => $email,
            'lead'     => $lead,
            'template' => $template
        ));
    }
}