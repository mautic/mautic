<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\VideoModel;
use Mautic\PageBundle\PageEvents;
use Mautic\QueueBundle\Queue\QueueName;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PublicController.
 */
class PublicController extends CommonFormController
{
    /**
     * @param         $slug
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function indexAction($slug, Request $request)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model    = $this->getModel('page');
        $security = $this->get('mautic.security');
        /** @var Page $entity */
        $entity = $model->getEntityBySlugs($slug);

        if (!empty($entity)) {
            $userAccess = $security->hasEntityAccess(
                'page:pages:viewown',
                'page:pages:viewother',
                $entity->getCreatedBy()
            );
            $published = $entity->isPublished();

            // Make sure the page is published or deny access if not
            if (!$published && !$userAccess) {
                // If the page has a redirect type, handle it
                if ($entity->getRedirectType() != null) {
                    $this->hitPage($model, $entity, $this->request, $entity->getRedirectType());

                    return $this->redirect($entity->getRedirectUrl(), $entity->getRedirectType());
                } else {
                    $this->hitPage($model, $entity, $this->request, 401);

                    return $this->accessDenied();
                }
            }

            $lead  = null;
            $query = null;
            if (!$userAccess) {
                /** @var LeadModel $leadModel */
                $leadModel = $this->getModel('lead');
                // Extract the lead from the request so it can be used to determine language if applicable
                $query = $model->getHitQuery($this->request, $entity);
                $lead  = $leadModel->getContactFromRequest($query);
            }

            // Correct the URL if it doesn't match up
            if (!$request->attributes->get('ignore_mismatch', 0)) {
                // Make sure URLs match up
                $url        = $model->generateUrl($entity, false);
                $requestUri = $this->request->getRequestUri();

                // Remove query when comparing
                $query = $this->request->getQueryString();
                if (!empty($query)) {
                    $requestUri = str_replace("?{$query}", '', $url);
                }

                // Redirect if they don't match
                if ($requestUri != $url) {
                    $this->hitPage($model, $entity, $this->request, 301, $lead, $query);

                    return $this->redirect($url, 301);
                }
            }

            // Check for variants
            list($parentVariant, $childrenVariants) = $entity->getVariants();

            // Is this a variant of another? If so, the parent URL should be used unless a user is logged in and previewing
            if ($parentVariant != $entity && !$userAccess) {
                $this->hitPage($model, $entity, $this->request, 301, $lead, $query);
                $url = $model->generateUrl($parentVariant, false);

                return $this->redirect($url, 301);
            }

            // First determine the A/B test to display if applicable
            if (!$userAccess) {
                // Check to see if a variant should be shown versus the parent but ignore if a user is previewing
                if (count($childrenVariants)) {
                    $variants      = [];
                    $variantWeight = 0;
                    $totalHits     = $entity->getVariantHits();

                    foreach ($childrenVariants as $id => $child) {
                        if ($child->isPublished()) {
                            $variantSettings = $child->getVariantSettings();
                            $variants[$id]   = [
                                'weight' => ($variantSettings['weight'] / 100),
                                'hits'   => $child->getVariantHits(),
                            ];
                            $variantWeight += $variantSettings['weight'];

                            // Count translations for this variant as well
                            $translations = $child->getTranslations(true);
                            /** @var Page $translation */
                            foreach ($translations as $translation) {
                                if ($translation->isPublished()) {
                                    $variants[$id]['hits'] += (int) $translation->getVariantHits();
                                }
                            }

                            $totalHits += $variants[$id]['hits'];
                        }
                    }

                    if (count($variants)) {
                        //check to see if this user has already been displayed a specific variant
                        $variantCookie = $this->request->cookies->get('mautic_page_'.$entity->getId());

                        if (!empty($variantCookie)) {
                            if (isset($variants[$variantCookie])) {
                                //if not the parent, show the specific variant already displayed to the visitor
                                if ($variantCookie !== $entity->getId()) {
                                    $entity = $childrenVariants[$variantCookie];
                                } //otherwise proceed with displaying parent
                            }
                        } else {
                            // Add parent weight
                            $variants[$entity->getId()] = [
                                'weight' => ((100 - $variantWeight) / 100),
                                'hits'   => $entity->getVariantHits(),
                            ];

                            // Count translations for the parent as well
                            $translations = $entity->getTranslations(true);
                            /** @var Page $translation */
                            foreach ($translations as $translation) {
                                if ($translation->isPublished()) {
                                    $variants[$entity->getId()]['hits'] += (int) $translation->getVariantHits();
                                }
                            }
                            $totalHits += $variants[$id]['hits'];

                            //determine variant to show
                            foreach ($variants as $id => &$variant) {
                                $variant['weight_deficit'] = ($totalHits) ? $variant['weight'] - ($variant['hits'] / $totalHits) : $variant['weight'];
                            }

                            // Reorder according to send_weight so that campaigns which currently send one at a time alternate
                            uasort(
                                $variants,
                                function ($a, $b) {
                                    if ($a['weight_deficit'] === $b['weight_deficit']) {
                                        if ($a['hits'] === $b['hits']) {
                                            return 0;
                                        }

                                        // if weight is the same - sort by least number displayed
                                        return ($a['hits'] < $b['hits']) ? -1 : 1;
                                    }

                                    // sort by the one with the greatest deficit first
                                    return ($a['weight_deficit'] > $b['weight_deficit']) ? -1 : 1;
                                }
                            );

                            //find the one with the most difference from weight

                            reset($variants);
                            $useId = key($variants);

                            //set the cookie - 14 days
                            $this->get('mautic.helper.cookie')->setCookie(
                                'mautic_page_'.$entity->getId(),
                                $useId,
                                3600 * 24 * 14
                            );

                            if ($useId != $entity->getId()) {
                                $entity = $childrenVariants[$useId];
                            }
                        }
                    }
                }

                // Now show the translation for the page or a/b test - only fetch a translation if a slug was not used
                if ($entity->isTranslation() && empty($entity->languageSlug)) {
                    list($translationParent, $translatedEntity) = $model->getTranslatedEntity(
                        $entity,
                        $lead,
                        $this->request
                    );

                    if ($translationParent && $translatedEntity !== $entity) {
                        if (!$this->request->get('ntrd', 0)) {
                            $url = $model->generateUrl($translatedEntity, false);
                            $this->hitPage($model, $entity, $this->request, 302, $lead, $query);

                            return $this->redirect($url, 302);
                        }
                    }
                }
            }

            // Generate contents
            $analytics = $this->get('mautic.helper.template.analytics')->getCode();

            $BCcontent = $entity->getContent();
            $content   = $entity->getCustomHtml();
            // This condition remains so the Mautic v1 themes would display the content
            if (empty($content) && !empty($BCcontent)) {
                /**
                 * @deprecated  BC support to be removed in 3.0
                 */
                $template = $entity->getTemplate();
                //all the checks pass so display the content
                $slots   = $this->factory->getTheme($template)->getSlots('page');
                $content = $entity->getContent();

                $this->processSlots($slots, $entity);

                // Add the GA code to the template assets
                if (!empty($analytics)) {
                    $this->factory->getHelper('template.assets')->addCustomDeclaration($analytics);
                }

                $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':page.html.php');

                $response = $this->render(
                    $logicalName,
                    [
                        'slots'    => $slots,
                        'content'  => $content,
                        'page'     => $entity,
                        'template' => $template,
                        'public'   => true,
                    ]
                );

                $content = $response->getContent();
            } else {
                if (!empty($analytics)) {
                    $content = str_replace('</head>', $analytics."\n</head>", $content);
                }
            }

            $this->get('templating.helper.assets')->addScript(
                $this->get('router')->generate('mautic_js', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'onPageDisplay_headClose',
                true,
                'mautic_js'
            );

            $event = new PageDisplayEvent($content, $entity);
            $this->get('event_dispatcher')->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
            $content = $event->getContent();

            $this->hitPage($model, $entity, $this->request, 200, $lead, $query);

            return new Response($content);
        }

        $this->hitPage($model, $entity, $this->request, 404);

        return $this->notFound();
    }

    /**
     * @param PageModel $model
     * @param $page
     * @param Request   $request
     * @param string    $code
     * @param Lead|null $lead
     * @param array     $query
     */
    private function hitPage(PageModel $model, $page, Request $request, $code = '200', Lead $lead = null, $query = [])
    {
        $ipLookupHelper = $this->get('mautic.helper.ip_lookup');
        $ipAddress      = $ipLookupHelper->getIpAddress();

        list($hitId, $trackingNewlyGenerated) = $model->generateHit(
            $page,
            $request,
            $ipAddress,
            $code,
            $lead,
            $query
        );

        //save hit to the cookie to use to update the exit time
        $cookieHelper = $this->get('mautic.helper.cookie');
        $cookieHelper->setCookie('mautic_referer_id', $hitId ?: null);

        $logger       = $this->get('monolog.logger.mautic');
        $queueService = $this->get('mautic.queue.service');
        if ($queueService->isQueueEnabled()) {
            $logger->log('info', 'using the queue');
            $msg = [
                'hitId'   => $hitId,
                'pageId'  => $page->getId(),
                'request' => $request,
                'isNew'   => $trackingNewlyGenerated,
            ];
            $queueService->publishToQueue(QueueName::PAGE_HIT, $msg);
        } else {
            $logger->log('info', 'not using the queue');
            $model->hitPage($hitId, $page, $request, $trackingNewlyGenerated);
        }
    }

    /**
     * @param $id
     *
     * @return Response|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function previewAction($id)
    {
        $model  = $this->getModel('page');
        $entity = $model->getEntity($id);

        if ($entity === null) {
            return $this->notFound();
        }

        $analytics = $this->factory->getHelper('template.analytics')->getCode();

        $BCcontent = $entity->getContent();
        $content   = $entity->getCustomHtml();
        if (empty($content) && !empty($BCcontent)) {
            $template = $entity->getTemplate();
            //all the checks pass so display the content
            $slots   = $this->factory->getTheme($template)->getSlots('page');
            $content = $entity->getContent();

            $this->processSlots($slots, $entity);

            // Add the GA code to the template assets
            if (!empty($analytics)) {
                $this->factory->getHelper('template.assets')->addCustomDeclaration($analytics);
            }

            $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':page.html.php');

            $response = $this->render(
                $logicalName,
                [
                    'slots'    => $slots,
                    'content'  => $content,
                    'page'     => $entity,
                    'template' => $template,
                    'public'   => true, // @deprecated Remove in 2.0
                ]
            );

            $content = $response->getContent();
        } else {
            if (!empty($analytics)) {
                $content = str_replace('</head>', $analytics."\n</head>", $content);
            }
        }

        $dispatcher = $this->get('event_dispatcher');
        if ($dispatcher->hasListeners(PageEvents::PAGE_ON_DISPLAY)) {
            $event = new PageDisplayEvent($content, $entity);
            $dispatcher->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
            $content = $event->getContent();
        }

        return new Response($content);
    }

    /**
     * @return Response
     */
    public function trackingImageAction()
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page');
        $this->hitPage($model, null, $this->request);

        return TrackingPixelHelper::getResponse($this->request);
    }

    /**
     * @return JsonResponse
     */
    public function trackingAction()
    {
        if (!$this->get('mautic.security')->isAnonymous()) {
            return new JsonResponse(
                [
                    'success' => 0,
                ]
            );
        }

        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page');
        $this->hitPage($model, null, $this->request);

        /** @var LeadModel $leadModel */
        $leadModel = $this->getModel('lead');

        list($lead, $trackingId, $generated) = $leadModel->getCurrentLead(true);

        return new JsonResponse(
            [
                'success' => 1,
                'id'      => $lead->getId(),
                'sid'     => $trackingId,
            ]
        );
    }

    /**
     * @param $redirectId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function redirectAction($redirectId)
    {
        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->getModel('page.redirect');
        $redirect      = $redirectModel->getRedirectById($redirectId);

        if (empty($redirect) || !$redirect->isPublished(false)) {
            throw $this->createNotFoundException($this->translator->trans('mautic.core.url.error.404'));
        }

        $pageModel = $this->getModel('page');
        $this->hitPage($pageModel, $redirect, $this->request);

        $url = $redirect->getUrl();

        // Ensure the URL does not have encoded ampersands
        $url = str_replace('&amp;', '&', $url);

        // Get query string
        $query = $this->request->query->all();

        // Unset the clickthrough
        unset($query['ct']);

        // Tak on anything left to the URL
        if (count($query)) {
            $url .= (strpos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($query);
        }

        // Search replace lead fields in the URL
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->getModel('lead');
        $lead      = $leadModel->getCurrentLead();
        $leadArray = $lead->getProfileFields();
        $url       = TokenHelper::findLeadTokens($url, $leadArray, true);

        return $this->redirect($url);
    }

    /**
     * PreProcess page slots for public view.
     *
     * @deprecated - to be removed in 3.0
     *
     * @param array $slots
     * @param Page  $entity
     */
    private function processSlots($slots, $entity)
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->factory->getHelper('template.assets');
        /** @var \Mautic\CoreBundle\Templating\Helper\SlotsHelper $slotsHelper */
        $slotsHelper = $this->factory->getHelper('template.slots');

        $content = $entity->getContent();

        foreach ($slots as $slot => $slotConfig) {
            // backward compatibility - if slotConfig array does not exist
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            if (isset($slotConfig['type']) && $slotConfig['type'] == 'slideshow') {
                if (isset($content[$slot])) {
                    $options = json_decode($content[$slot], true);
                } else {
                    $options = [
                        'width'            => '100%',
                        'height'           => '250px',
                        'background_color' => 'transparent',
                        'arrow_navigation' => false,
                        'dot_navigation'   => true,
                        'interval'         => 5000,
                        'pause'            => 'hover',
                        'wrap'             => true,
                        'keyboard'         => true,
                    ];
                }

                // Create sample slides for first time or if all slides were deleted
                if (empty($options['slides'])) {
                    $options['slides'] = [
                        [
                            'order'            => 0,
                            'background-image' => $assetsHelper->getUrl('media/images/mautic_logo_lb200.png'),
                            'captionheader'    => 'Caption 1',
                        ],
                        [
                            'order'            => 1,
                            'background-image' => $assetsHelper->getUrl('media/images/mautic_logo_db200.png'),
                            'captionheader'    => 'Caption 2',
                        ],
                    ];
                }

                // Order slides
                usort(
                    $options['slides'],
                    function ($a, $b) {
                        return strcmp($a['order'], $b['order']);
                    }
                );

                $options['slot']   = $slot;
                $options['public'] = true;

                $renderingEngine = $this->container->get('templating')->getEngine('MauticPageBundle:Page:Slots/slideshow.html.php');
                $slotsHelper->set($slot, $renderingEngine->render('MauticPageBundle:Page:Slots/slideshow.html.php', $options));
            } elseif (isset($slotConfig['type']) && $slotConfig['type'] == 'textarea') {
                $value = isset($content[$slot]) ? nl2br($content[$slot]) : '';
                $slotsHelper->set($slot, $value);
            } else {
                // Fallback for other types like html, text, textarea and all unknown
                $value = isset($content[$slot]) ? $content[$slot] : '';
                $slotsHelper->set($slot, $value);
            }
        }

        $parentVariant = $entity->getVariantParent();
        $title         = (!empty($parentVariant)) ? $parentVariant->getTitle() : $entity->getTitle();
        $slotsHelper->set('pageTitle', $title);
    }

    /**
     * Track video views.
     */
    public function hitVideoAction()
    {
        // Only track XMLHttpRequests, because the hit should only come from there
        if ($this->request->isXmlHttpRequest()) {
            /** @var VideoModel $model */
            $model = $this->getModel('page.video');

            try {
                $model->hitVideo($this->request);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false]);
            }

            return new JsonResponse(['success' => true]);
        }

        return new Response();
    }

    /**
     * Get the ID of the currently tracked Contact.
     *
     * @return JsonResponse
     */
    public function getContactIdAction()
    {
        $data = [];
        if ($this->get('mautic.security')->isAnonymous()) {
            /** @var LeadModel $leadModel */
            $leadModel = $this->getModel('lead');

            list($lead, $trackingId, $generated) = $leadModel->getCurrentLead(true);
            $data                                = [
                'id'  => $lead->getId(),
                'sid' => $trackingId,
            ];
        }

        return new JsonResponse($data);
    }
}
