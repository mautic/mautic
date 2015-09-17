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
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\EmailBundle\Swiftmailer\Transport\InterfaceCallbackTransport;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonFormController
{
    public function indexAction($idHash)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->factory->getModel('email');
        $stat  = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $entity = $stat->getEmail();
            $model->hitEmail($stat, $this->request, true);

            // Check for stored copy
            $content = $stat->getCopy();

            if (!empty($content)) {
                // Copy stored in stats
                $tokens = $stat->getTokens();
                if (!empty($tokens)) {
                    // Override tracking_pixel so as to not cause a double hit
                    $tokens['{tracking_pixel}'] = MailHelper::getBlankPixel();

                    $content = str_ireplace(array_keys($tokens), $tokens, $content);
                }
            } else {
                // Old way where stats didn't store content

                //the lead needs to have fields populated
                $statLead = $stat->getLead();
                $lead     = $this->factory->getModel('lead')->getLead($statLead->getId());
                $template = $entity->getTemplate();
                if (!empty($template)) {
                    $slots = $this->factory->getTheme($template)->getSlots('email');

                    $response = $this->render(
                        'MauticEmailBundle::public.html.php',
                        array(
                            'inBrowser' => true,
                            'slots'     => $slots,
                            'content'   => $entity->getContent(),
                            'email'     => $entity,
                            'lead'      => $lead,
                            'template'  => $template
                        )
                    );

                    //replace tokens
                    $content = $response->getContent();
                } else {
                    $content = $entity->getCustomHtml();
                }
                $content = EmojiHelper::toEmoji($content, 'short');

                $tokens = $stat->getTokens();

                // Override tracking_pixel so as to not cause a double hit
                $tokens['{tracking_pixel}'] = MailHelper::getBlankPixel();

                $event = new EmailSendEvent(
                    null,
                    array(
                        'content' => $content,
                        'lead'    => $lead,
                        'email'   => $entity,
                        'idHash'  => $idHash,
                        'tokens'  => $tokens
                    )
                );
                $this->factory->getDispatcher()->dispatch(EmailEvents::EMAIL_ON_DISPLAY, $event);
                $content = $event->getContent(true);
            }

            $analytics = htmlspecialchars_decode($this->factory->getParameter('google_analytics', ''));

            // Check for html doc
            if (strpos($content, '<html>') === false) {
                $content = "<html>\n<head>{$analytics}</head>\n<body>{$content}</body>\n</html>";
            } elseif (strpos($content, '<head>') === false) {
                $content = str_replace('<html>', "<html>\n<head>\n{$analytics}\n</head>", $content);
            } elseif (!empty($analytics)) {
                $content = str_replace('</head>', $analytics."\n</head>", $content);
            }

            return new Response($content);
        }

        $this->notFound();
    }

    /**
     * @param $idHash
     *
     * @return Response
     */
    public function trackingImageAction($idHash)
    {
        $response = TrackingPixelHelper::getResponse($this->request);

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model    = $this->factory->getModel('email');
        $model->hitEmail($idHash, $this->request);

        $size = strlen($response->getContent());
        $response->headers->set('Content-Length', $size);
        $response->headers->set('Connection', 'close');

        //generate image
        return $response;
    }

    /**
     * @param $idHash
     *
     * @return Response
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function unsubscribeAction($idHash)
    {
        //find the email

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model      = $this->factory->getModel('email');
        $translator = $this->get('translator');
        $stat       = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $email = $stat->getEmail();
            $lead  = $stat->getLead();

            // Set the lead as current lead
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->factory->getModel('lead');
            $leadModel->setCurrentLead($lead);

            $model->setDoNotContact($stat, $translator->trans('mautic.email.dnc.unsubscribed'), 'unsubscribed');

            $message = $this->factory->getParameter('unsubscribe_message');
            if (!$message) {
                $message = $this->factory->getTranslator()->trans(
                    'mautic.email.unsubscribed.success',
                    array(
                        '%resubscribeUrl%' => '|URL|',
                        '%email%'          => '|EMAIL|'
                    )
                );
            }
            $message = str_replace(
                array(
                    '|URL|',
                    '|EMAIL|'
                ),
                array(
                    $this->generateUrl('mautic_email_resubscribe', array('idHash' => $idHash)),
                    $stat->getEmailAddress()
                ), $message
            );

            if ($email !== null) {
                $template = $email->getTemplate();

                /** @var \Mautic\FormBundle\Entity\Form $unsubscribeForm */
                $unsubscribeForm = $email->getUnsubscribeForm();

                if ($unsubscribeForm != null && $unsubscribeForm->isPublished()) {
                    $formTemplate = $unsubscribeForm->getTemplate();
                    $formModel    = $this->factory->getModel('form');
                    $formContent  = '<div class="mautic-unsubscribeform">'.$formModel->getContent($unsubscribeForm).'</div>';
                }
            }
        } else {
            $email   = $lead = false;
            $message = '';
        }

        if (empty($template) && empty($formTemplate)) {
            $template = $this->factory->getParameter('theme');
        } else if (!empty($formTemplate)) {
            $template = $formTemplate;
        }
        $theme = $this->factory->getTheme($template);
        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }
        $config = $theme->getConfig();

        $viewParams      = array(
            'email'    => $email,
            'lead'     => $lead,
            'template' => $template,
            'message'  => $message,
            'type'     => 'notice',
            'name'     => $translator->trans('mautic.email.unsubscribe')
        );
        $contentTemplate = 'MauticCoreBundle::message.html.php';

        if (!empty($formContent)) {
            $viewParams['content'] = $formContent;
            if (in_array('form', $config['features'])) {
                $contentTemplate = 'MauticFormBundle::form.html.php';
            }
        }

        return $this->render($contentTemplate, $viewParams);
    }

    /**
     * @param $idHash
     *
     * @return Response
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
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

            // Set the lead as current lead
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->factory->getModel('lead');
            $leadModel->setCurrentLead($lead);

            $model->removeDoNotContact($stat->getEmailAddress());


            $message = $this->factory->getParameter('resubscribe_message');
            if (!$message) {
                $message = $this->factory->getTranslator()->trans(
                    'mautic.email.resubscribed.success',
                    array(
                        '%unsubscribedUrl%' => '|URL|',
                        '%email%'          => '|EMAIL|'
                    )
                );
            }
            $message = str_replace(
                array(
                    '|URL|',
                    '|EMAIL|'
                ),
                array(
                    $this->generateUrl('mautic_email_unsubscribe', array('idHash' => $idHash)),
                    $stat->getEmailAddress()
                ), $message
            );

        } else {
            $email = $lead = false;
        }

        $template = ($email !== null) ? $email->getTemplate() : $this->factory->getParameter('theme');
        $theme    = $this->factory->getTheme($template);

        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }

        // Ensure template still exists
        $theme = $this->factory->getTheme($template);
        if (empty($theme) || $theme->getTheme() !== $template) {
            $template = $this->factory->getParameter('theme');
        }

        return $this->render('MauticCoreBundle::message.html.php', array(
            'message'  => $message,
            'type'     => 'notice',
            'email'    => $email,
            'lead'     => $lead,
            'template' => $template
        ));
    }

    /**
     * Handles mailer transport webhook post
     *
     * @param $transport
     *
     * @return Response
     */
    public function mailerCallbackAction($transport)
    {
        ignore_user_abort(true);

        // Check to see if transport matches currently used transport
        $currentTransport = $this->factory->getMailer()->getTransport();

        if ($currentTransport instanceof InterfaceCallbackTransport && $currentTransport->getCallbackPath() == $transport) {
            $response = $currentTransport->handleCallbackResponse($this->request, $this->factory);
            if (!empty($response['bounces'])) {
                /** @var \Mautic\EmailBundle\Model\EmailModel $model */
                $model = $this->factory->getModel('email');
                $model->updateBouncedStats($response['bounces']);
            }

            return new Response('success');
        }

        $this->notFound();
    }

    /**
     * Preview email
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function previewAction($objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model  = $this->factory->getModel('email');
        $entity = $model->getEntity($objectId);

        if (
            ($this->factory->getSecurity()->isAnonymous() && !$entity->isPublished()) ||
            (!$this->factory->getSecurity()->isAnonymous() && !$this->factory->getSecurity()->hasEntityAccess('email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy()))
        ) {
            return $this->accessDenied();
        }

        //bogus ID
        $idHash = 'xxxxxxxxxxxxxx';

        $template = $entity->getTemplate();
        if (!empty($template)) {
            $slots = $this->factory->getTheme($template)->getSlots('email');

            $response = $this->render(
                'MauticEmailBundle::public.html.php',
                array(
                    'inBrowser' => true,
                    'slots'     => $slots,
                    'content'   => $entity->getContent(),
                    'email'     => $entity,
                    'lead'      => null,
                    'template'  => $template
                )
            );

            //replace tokens
            $content = $response->getContent();
        } else {
            $content = $entity->getCustomHtml();
        }

        // Convert emojis
        $content = EmojiHelper::toEmoji($content, 'short');

        // Override tracking_pixel
        $tokens = array('{tracking_pixel}' => '');

        // Prepare a fake lead
        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel   = $this->factory->getModel('lead.field');
        $fields       = $fieldModel->getFieldList(false, false);
        array_walk($fields, function(&$field) {
            $field = "[$field]";
        });
        $fields['id'] = 0;

        // Generate and replace tokens
        $event = new EmailSendEvent(
            null,
            array(
                'content'      => $content,
                'email'        => $entity,
                'idHash'       => $idHash,
                'tokens'       => $tokens,
                'internalSend' => true,
                'lead'         => $fields
            )
        );
        $this->factory->getDispatcher()->dispatch(EmailEvents::EMAIL_ON_DISPLAY, $event);

        $content = $event->getContent(true);

        return new Response($content);

    }
}
