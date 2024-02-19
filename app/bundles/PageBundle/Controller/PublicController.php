<?php

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Event\TrackingEvent;
use Mautic\PageBundle\Helper\TrackingHelper;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\Tracking404Model;
use Mautic\PageBundle\Model\VideoModel;
use Mautic\PageBundle\PageEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PublicController extends CommonFormController
{
    /**
     * @return Response
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function indexAction(
        Request $request,
        ContactRequestHelper $contactRequestHelper,
        CookieHelper $cookieHelper,
        AnalyticsHelper $analyticsHelper,
        AssetsHelper $assetsHelper,
        Tracking404Model $tracking404Model,
        UrlGeneratorInterface $router,
        $slug)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model    = $this->getModel('page');
        $security = $this->security;
        /** @var Page|bool $entity */
        $entity = $model->getEntityBySlugs($slug);

        // Do not hit preference center pages
        if (!empty($entity) && !$entity->getIsPreferenceCenter()) {
            $userAccess = $security->hasEntityAccess('page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy());
            $published  = $entity->isPublished();

            // Make sure the page is published or deny access if not
            if (!$published && !$userAccess) {
                // If the page has a redirect type, handle it
                if (null != $entity->getRedirectType()) {
                    $model->hitPage($entity, $request, $entity->getRedirectType());

                    return $this->redirect($entity->getRedirectUrl(), (int) $entity->getRedirectType());
                } else {
                    $model->hitPage($entity, $request, 401);

                    return $this->accessDenied();
                }
            }

            $lead  = null;
            $query = null;
            if (!$userAccess) {
                // Extract the lead from the request so it can be used to determine language if applicable
                $query = $model->getHitQuery($request, $entity);
                $lead  = $contactRequestHelper->getContactFromQuery($query);
            }

            // Correct the URL if it doesn't match up
            if (!$request->attributes->get('ignore_mismatch', 0)) {
                // Make sure URLs match up
                $url        = $model->generateUrl($entity, false);
                $requestUri = $request->getRequestUri();

                // Remove query when comparing
                $query = $request->getQueryString();
                if (!empty($query)) {
                    $requestUri = str_replace("?{$query}", '', $url);
                }

                // Redirect if they don't match
                if ($requestUri != $url) {
                    $model->hitPage($entity, $request, 301, $lead, $query);

                    return $this->redirect($url, 301);
                }
            }

            // Check for variants
            [$parentVariant, $childrenVariants] = $entity->getVariants();

            // Is this a variant of another? If so, the parent URL should be used unless a user is logged in and previewing
            if ($parentVariant != $entity && !$userAccess) {
                $model->hitPage($entity, $request, 301, $lead, $query);
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
                        // check to see if this user has already been displayed a specific variant
                        $variantCookie = $request->cookies->get('mautic_page_'.$entity->getId());

                        if (!empty($variantCookie)) {
                            if (isset($variants[$variantCookie])) {
                                // if not the parent, show the specific variant already displayed to the visitor
                                if ((int) $variantCookie !== $entity->getId()) {
                                    $entity = $childrenVariants[$variantCookie];
                                } // otherwise proceed with displaying parent
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

                            // determine variant to show
                            foreach ($variants as &$variant) {
                                $variant['weight_deficit'] = ($totalHits) ? $variant['weight'] - ($variant['hits'] / $totalHits) : $variant['weight'];
                            }

                            // Reorder according to send_weight so that campaigns which currently send one at a time alternate
                            uasort(
                                $variants,
                                function ($a, $b): int {
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

                            // find the one with the most difference from weight
                            $useId = array_key_first($variants);

                            // set the cookie - 14 days
                            $cookieHelper->setCookie(
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
                    [$translationParent, $translatedEntity] = $model->getTranslatedEntity(
                        $entity,
                        $lead,
                        $request
                    );

                    if ($translationParent && $translatedEntity !== $entity) {
                        if (!$request->get('ntrd', 0)) {
                            $url = $model->generateUrl($translatedEntity, false);
                            $model->hitPage($entity, $request, 302, $lead, $query);

                            return $this->redirect($url, 302);
                        }
                    }
                }
            }

            // Generate contents
            $analytics = $analyticsHelper->getCode();

            $BCcontent = $entity->getContent();
            $content   = $entity->getCustomHtml();
            // This condition remains so the Mautic v1 themes would display the content
            if (empty($content) && !empty($BCcontent)) {
                /**
                 * @deprecated  BC support to be removed in 3.0
                 */
                $template = $entity->getTemplate();
                // all the checks pass so display the content
                $slots   = $this->factory->getTheme($template)->getSlots('page');
                $content = $entity->getContent();

                $this->processSlots($slots, $entity);

                // Add the GA code to the template assets
                if (!empty($analytics)) {
                    $this->factory->getHelper('template.assets')->addCustomDeclaration($analytics);
                }

                $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate('@themes/'.$template.'/html/page.html.twig');

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
                if ($entity->getNoIndex()) {
                    $content = str_replace('</head>', "<meta name=\"robots\" content=\"noindex\">\n</head>", $content);
                }
            }

            $assetsHelper->addScript(
                $router->generate('mautic_js', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'onPageDisplay_headClose',
                true,
                'mautic_js'
            );

            $event = new PageDisplayEvent($content, $entity);
            $this->dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY);
            $content = $event->getContent();

            $model->hitPage($entity, $request, 200, $lead, $query);

            return new Response($content);
        }

        if (false !== $entity && $tracking404Model->isTrackable()) {
            $tracking404Model->hitPage($entity, $request);
        }

        return $this->notFound();
    }

    /**
     * @return Response|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function previewAction($id)
    {
        $model  = $this->getModel('page');
        $entity = $model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        $analytics = $this->factory->getHelper('twig.analytics')->getCode();

        $BCcontent = $entity->getContent();
        $content   = $entity->getCustomHtml();
        if (empty($content) && !empty($BCcontent)) {
            $template = $entity->getTemplate();
            // all the checks pass so display the content
            $slots   = $this->factory->getTheme($template)->getSlots('page');
            $content = $entity->getContent();

            $this->processSlots($slots, $entity);

            // Add the GA code to the template assets
            if (!empty($analytics)) {
                $this->factory->getHelper('template.assets')->addCustomDeclaration($analytics);
            }

            $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate('@themes/'.$template.'/html/page.html.twig');

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
            $content = str_replace('</head>', $analytics.$this->renderView('@MauticPage/Page/preview_header.html.twig')."\n</head>", $content);
        }

        if ($this->dispatcher->hasListeners(PageEvents::PAGE_ON_DISPLAY)) {
            $event = new PageDisplayEvent($content, $entity);
            $this->dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY);
            $content = $event->getContent();
        }

        return new Response($content);
    }

    /**
     * @return Response
     *
     * @throws \Exception
     */
    public function trackingImageAction(Request $request)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page');
        $model->hitPage(null, $request);

        return TrackingPixelHelper::getResponse($request);
    }

    /**
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function trackingAction(
        Request $request,
        DeviceTrackingServiceInterface $deviceTrackingService,
        TrackingHelper $trackingHelper,
        ContactTracker $contactTracker
    ) {
        $notSuccessResponse = new JsonResponse(
            [
                'success' => 0,
            ]
        );
        if (!$this->security->isAnonymous()) {
            return $notSuccessResponse;
        }

        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page');

        try {
            $model->hitPage(null, $request);
        } catch (InvalidDecodedStringException) {
            // do not track invalid ct
            return $notSuccessResponse;
        }

        $lead          = $contactTracker->getContact();
        $trackedDevice = $deviceTrackingService->getTrackedDevice();
        $trackingId    = (null === $trackedDevice ? null : $trackedDevice->getTrackingId());

        $sessionValue   = $trackingHelper->getSession(true);

        $event = new TrackingEvent($lead, $request, $sessionValue);
        $this->dispatcher->dispatch($event, PageEvents::ON_CONTACT_TRACKED);

        return new JsonResponse(
            [
                'success'   => 1,
                'id'        => ($lead) ? $lead->getId() : null,
                'sid'       => $trackingId,
                'device_id' => $trackingId,
                'events'    => $event->getResponse()->all(),
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function redirectAction(
        Request $request,
        ContactRequestHelper $contactRequestHelper,
        PrimaryCompanyHelper $primaryCompanyHelper,
        IpLookupHelper $ipLookupHelper,
        LoggerInterface $logger,
        $redirectId
    ): \Symfony\Component\HttpFoundation\RedirectResponse {
        $logger->debug('Attempting to load redirect with tracking_id of: '.$redirectId);

        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->getModel('page.redirect');
        $redirect      = $redirectModel->getRedirectById($redirectId);

        $logger->debug('Executing Redirect: '.$redirect);

        if (null === $redirect || !$redirect->isPublished(false)) {
            $logger->debug('Redirect with tracking_id of '.$redirectId.' not found');

            $url = ($redirect) ? $redirect->getUrl() : 'n/a';

            throw $this->createNotFoundException($this->translator->trans('mautic.core.url.error.404', ['%url%' => $url]));
        }

        // Ensure the URL does not have encoded ampersands
        $url = UrlHelper::decodeAmpersands($redirect->getUrl());

        // Get query string
        $query = $request->query->all();

        $ct = $query['ct'] ?? null;

        // Tak on anything left to the URL
        if (count($query)) {
            $url = UrlHelper::appendQueryToUrl($url, http_build_query($query));
        }

        // If the IP address is not trackable, it means it came form a configured "do not track" IP or a "do not track" user agent
        // This prevents simulated clicks from 3rd party services such as URL shorteners from simulating clicks
        $ipAddress = $ipLookupHelper->getIpAddress();

        if ($ct) {
            if ($ipAddress->isTrackable()) {
                // Search replace lead fields in the URL
                /** @var PageModel $pageModel */
                $pageModel = $this->getModel('page');

                try {
                    $lead = $contactRequestHelper->getContactFromQuery(['ct' => $ct]);

                    $pageModel->hitPage($redirect, $request, 200, $lead);
                } catch (InvalidDecodedStringException $e) {
                    // Invalid ct value so we must unset it
                    // and process the request without it

                    $logger->error(sprintf('Invalid clickthrough value: %s', $ct), ['exception' => $e]);

                    $request->request->set('ct', '');
                    $request->query->set('ct', '');
                    $lead = $contactRequestHelper->getContactFromQuery();
                    $pageModel->hitPage($redirect, $request, 200, $lead);
                }

                $leadArray = ($lead) ? $primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($lead) : [];

                $url = TokenHelper::findLeadTokens($url, $leadArray, true);
            }

            if (str_contains($url, $this->generateUrl('mautic_asset_download'))) {
                if (strpos($url, '&')) {
                    $url .= '&ct='.$ct;
                } else {
                    $url .= '?ct='.$ct;
                }
            }
        }

        $url = UrlHelper::sanitizeAbsoluteUrl($url);

        if (!UrlHelper::isValidUrl($url)) {
            throw $this->createNotFoundException($this->translator->trans('mautic.core.url.error.404', ['%url%' => $url]));
        }

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
    private function processSlots($slots, $entity): void
    {
        /** @var \Mautic\CoreBundle\Twig\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->factory->getHelper('template.assets');
        /** @var \Mautic\CoreBundle\Twig\Helper\SlotsHelper $slotsHelper */
        $slotsHelper = $this->factory->getHelper('template.slots');

        $content = $entity->getContent();

        foreach ($slots as $slot => $slotConfig) {
            // backward compatibility - if slotConfig array does not exist
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            if (isset($slotConfig['type']) && 'slideshow' == $slotConfig['type']) {
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
                            'background-image' => $assetsHelper->getOverridableUrl('images/mautic_logo_lb200.png'),
                            'captionheader'    => 'Caption 1',
                        ],
                        [
                            'order'            => 1,
                            'background-image' => $assetsHelper->getOverridableUrl('images/mautic_logo_db200.png'),
                            'captionheader'    => 'Caption 2',
                        ],
                    ];
                }

                // Order slides
                usort(
                    $options['slides'],
                    fn ($a, $b): int => strcmp($a['order'], $b['order'])
                );

                $options['slot']   = $slot;
                $options['public'] = true;
            } elseif (isset($slotConfig['type']) && 'textarea' == $slotConfig['type']) {
                $value = isset($content[$slot]) ? nl2br($content[$slot]) : '';
                $slotsHelper->set($slot, $value);
            } else {
                // Fallback for other types like html, text, textarea and all unknown
                $value = $content[$slot] ?? '';
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
    public function hitVideoAction(Request $request)
    {
        // Only track XMLHttpRequests, because the hit should only come from there
        if ($request->isXmlHttpRequest()) {
            /** @var VideoModel $model */
            $model = $this->getModel('page.video');

            try {
                $model->hitVideo($request);
            } catch (\Exception) {
                return new JsonResponse(['success' => false]);
            }

            return new JsonResponse(['success' => true]);
        }

        return new Response();
    }

    /**
     * Get the ID of the currently tracked Contact.
     */
    public function getContactIdAction(DeviceTrackingServiceInterface $trackedDeviceService, ContactTracker $contactTracker): JsonResponse
    {
        $data = [];
        if ($this->security->isAnonymous()) {
            $lead          = $contactTracker->getContact();
            $trackedDevice = $trackedDeviceService->getTrackedDevice();
            $trackingId    = (null === $trackedDevice ? null : $trackedDevice->getTrackingId());
            $data          = [
                'id'        => ($lead) ? $lead->getId() : null,
                'sid'       => $trackingId,
                'device_id' => $trackingId,
            ];
        }

        return new JsonResponse($data);
    }
}
