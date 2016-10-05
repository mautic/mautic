<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Swiftmailer\Transport\InterfaceCallbackTransport;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonFormController
{
    public function indexAction($idHash)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');
        $stat  = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            if ($this->get('mautic.security')->isAnonymous()) {
                $model->hitEmail($stat, $this->request, true);
            }

            $tokens = $stat->getTokens();
            if (is_array($tokens)) {
                // Override tracking_pixel so as to not cause a double hit
                $tokens['{tracking_pixel}'] = MailHelper::getBlankPixel();
            }

            if ($copy = $stat->getStoredCopy()) {
                $subject = $copy->getSubject();
                $content = $copy->getBody();

                // Convert emoji
                $content = EmojiHelper::toEmoji($content, 'short');
                $subject = EmojiHelper::toEmoji($subject, 'short');

                // Replace tokens
                if (!empty($tokens)) {
                    $content = str_ireplace(array_keys($tokens), $tokens, $content);
                    $subject = str_ireplace(array_keys($tokens), $tokens, $subject);
                }
            } else {
                $subject = '';
                $content = '';
            }

            // Add analytics
            $analytics = $this->factory->getHelper('template.analytics')->getCode();

            // Check for html doc
            if (strpos($content, '<html>') === false) {
                $content = "<html>\n<head>{$analytics}</head>\n<body>{$content}</body>\n</html>";
            } elseif (strpos($content, '<head>') === false) {
                $content = str_replace('<html>', "<html>\n<head>\n{$analytics}\n</head>", $content);
            } elseif (!empty($analytics)) {
                $content = str_replace('</head>', $analytics."\n</head>", $content);
            }

            // Add subject as title
            if (!empty($subject)) {
                if (strpos($content, '<title></title>') !== false) {
                    $content = str_replace('<title></title>', "<title>$subject</title>", $content);
                } elseif (strpos($content, '<title>') === false) {
                    $content = str_replace('<head>', "<head>\n<title>$subject</title>", $content);
                }
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
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');
        $model->hitEmail($idHash, $this->request);

        return TrackingPixelHelper::getResponse($this->request);
    }

    /**
     * @param $idHash
     *
     * @return Response
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function unsubscribeAction($idHash)
    {
        // Find the email
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model      = $this->getModel('email');
        $translator = $this->get('translator');
        $stat       = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $email = $stat->getEmail();
            $lead  = $stat->getLead();

            if ($lead) {
                // Set the lead as current lead
                /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
                $leadModel = $this->getModel('lead');
                $leadModel->setCurrentLead($lead);
            }

            $model->setDoNotContact($stat, $translator->trans('mautic.email.dnc.unsubscribed'), DoNotContact::UNSUBSCRIBED);

            $message = $this->coreParametersHelper->getParameter('unsubscribe_message');
            if (!$message) {
                $message = $translator->trans(
                    'mautic.email.unsubscribed.success',
                    [
                        '%resubscribeUrl%' => '|URL|',
                        '%email%'          => '|EMAIL|',
                    ]
                );
            }
            $message = str_replace(
                [
                    '|URL|',
                    '|EMAIL|',
                ],
                [
                    $this->generateUrl('mautic_email_resubscribe', ['idHash' => $idHash]),
                    $stat->getEmailAddress(),
                ],
                $message
            );

            if ($email !== null) {
                $template = $email->getTemplate();

                /** @var \Mautic\FormBundle\Entity\Form $unsubscribeForm */
                $unsubscribeForm = $email->getUnsubscribeForm();

                if ($unsubscribeForm != null && $unsubscribeForm->isPublished()) {
                    $formTemplate = $unsubscribeForm->getTemplate();
                    $formModel    = $this->getModel('form');
                    $formContent  = '<div class="mautic-unsubscribeform">'.$formModel->getContent($unsubscribeForm).'</div>';
                }
            }
        } else {
            $email   = $lead   = false;
            $message = $translator->trans('mautic.email.stat_record.not_found');
        }

        if (empty($template) && empty($formTemplate)) {
            $template = $this->coreParametersHelper->getParameter('theme');
        } elseif (!empty($formTemplate)) {
            $template = $formTemplate;
        }
        $theme = $this->factory->getTheme($template);
        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }
        $config = $theme->getConfig();

        $viewParams = [
            'email'    => $email,
            'lead'     => $lead,
            'template' => $template,
            'message'  => $message,
            'type'     => 'notice',
            'name'     => $translator->trans('mautic.email.unsubscribe'),
        ];

        $contentTemplate = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':message.html.php');

        if (!empty($formContent)) {
            $viewParams['content'] = $formContent;
            if (in_array('form', $config['features'])) {
                $contentTemplate = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':form.html.php');
            } else {
                $contentTemplate = 'MauticFormBundle::form.html.php';
            }
        }

        return $this->render($contentTemplate, $viewParams);
    }

    /**
     * @param $idHash
     *
     * @return Response
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function resubscribeAction($idHash)
    {
        //find the email
        $model = $this->getModel('email');
        $stat  = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $email = $stat->getEmail();
            $lead  = $stat->getLead();

            if ($lead) {
                // Set the lead as current lead
                /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
                $leadModel = $this->getModel('lead');
                $leadModel->setCurrentLead($lead);
            }

            $model->removeDoNotContact($stat->getEmailAddress());

            $message = $this->coreParametersHelper->getParameter('resubscribe_message');
            if (!$message) {
                $message = $this->translator->trans(
                    'mautic.email.resubscribed.success',
                    [
                        '%unsubscribedUrl%' => '|URL|',
                        '%email%'           => '|EMAIL|',
                    ]
                );
            }
            $message = str_replace(
                [
                    '|URL|',
                    '|EMAIL|',
                ],
                [
                    $this->generateUrl('mautic_email_unsubscribe', ['idHash' => $idHash]),
                    $stat->getEmailAddress(),
                ],
                $message
            );
        } else {
            $email   = $lead   = false;
            $message = $this->translator->trans('mautic.email.stat_record.not_found');
        }

        $template = ($email !== null) ? $email->getTemplate() : $this->coreParametersHelper->getParameter('theme');
        $theme    = $this->factory->getTheme($template);

        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }

        // Ensure template still exists
        $theme = $this->factory->getTheme($template);
        if (empty($theme) || $theme->getTheme() !== $template) {
            $template = $this->coreParametersHelper->getParameter('theme');
        }

        $analytics = $this->factory->getHelper('template.analytics')->getCode();

        if (!empty($analytics)) {
            $this->factory->getHelper('template.assets')->addCustomDeclaration($analytics);
        }

        $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':message.html.php');

        return $this->render(
            $logicalName,
            [
                'message'  => $message,
                'type'     => 'notice',
                'email'    => $email,
                'lead'     => $lead,
                'template' => $template,
            ]
        );
    }

    /**
     * Handles mailer transport webhook post.
     *
     * @param $transport
     *
     * @return Response
     */
    public function mailerCallbackAction($transport)
    {
        ignore_user_abort(true);

        // Use the real transport as the one in Mailer could be SpoolTransport if the system is configured to queue
        // Can't use swiftmailer.transport.real because it's not set for when queue is disabled
        $transportParam   = $this->get('mautic.helper.core_parameters')->getParameter(('mailer_transport'));
        $currentTransport = $this->get('swiftmailer.mailer.transport.'.$transportParam);

        if ($currentTransport instanceof InterfaceCallbackTransport && $currentTransport->getCallbackPath() == $transport) {
            $response = $currentTransport->handleCallbackResponse($this->request, $this->factory);

            if (is_array($response)) {
                /** @var \Mautic\EmailBundle\Model\EmailModel $model */
                $model = $this->getModel('email');

                $model->processMailerCallback($response);
            }

            return new Response('success');
        }

        $this->notFound();
    }

    /**
     * Preview email.
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function previewAction($objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model       = $this->getModel('email');
        $emailEntity = $model->getEntity($objectId);

        if (
            ($this->get('mautic.security')->isAnonymous() && !$emailEntity->isPublished())
            || (!$this->get('mautic.security')->isAnonymous()
                && !$this->get('mautic.security')->hasEntityAccess(
                    'email:emails:viewown',
                    'email:emails:viewother',
                    $emailEntity->getCreatedBy()
                ))
        ) {
            return $this->accessDenied();
        }

        //bogus ID
        $idHash = 'xxxxxxxxxxxxxx';

        $BCcontent = $emailEntity->getContent();
        $content   = $emailEntity->getCustomHtml();
        if (empty($content) && !empty($BCcontent)) {
            $template = $emailEntity->getTemplate();
            $slots    = $this->factory->getTheme($template)->getSlots('email');

            $assetsHelper = $this->factory->getHelper('template.assets');

            $assetsHelper->addCustomDeclaration('<meta name="robots" content="noindex">');

            $this->processSlots($slots, $emailEntity);

            $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':email.html.php');

            $response = $this->render(
                $logicalName,
                [
                    'inBrowser' => true,
                    'slots'     => $slots,
                    'content'   => $emailEntity->getContent(),
                    'email'     => $emailEntity,
                    'lead'      => null,
                    'template'  => $template,
                ]
            );

            //replace tokens
            $content = $response->getContent();
        }

        // Convert emojis
        $content = EmojiHelper::toEmoji($content, 'short');

        // Override tracking_pixel
        $tokens = ['{tracking_pixel}' => ''];

        // Prepare a fake lead
        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel = $this->getModel('lead.field');
        $fields     = $fieldModel->getFieldList(false, false);
        array_walk(
            $fields,
            function (&$field) {
                $field = "[$field]";
            }
        );
        $fields['id'] = 0;

        // Generate and replace tokens
        $event = new EmailSendEvent(
            null,
            [
                'content'      => $content,
                'email'        => $emailEntity,
                'idHash'       => $idHash,
                'tokens'       => $tokens,
                'internalSend' => true,
                'lead'         => $fields,
            ]
        );
        $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_DISPLAY, $event);

        $content = $event->getContent(true);

        return new Response($content);
    }

    /**
     * @param $slots
     * @param Email $entity
     */
    public function processSlots($slots, $entity)
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\SlotsHelper $slotsHelper */
        $slotsHelper = $this->factory->getHelper('template.slots');

        $content = $entity->getContent();

        foreach ($slots as $slot => $slotConfig) {
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            $value = isset($content[$slot]) ? $content[$slot] : '';
            $slotsHelper->set($slot, $value);
        }
    }
}
