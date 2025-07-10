<?php

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\LeadModel;
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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PublicController extends AbstractFormController
{
    /**
     * @param string $slug
     *
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
        ThemeHelper $themeHelper,
        Tracking404Model $tracking404Model,
        RouterInterface $router,
        DeviceTrackingServiceInterface $deviceTrackingService,
        $slug)
    {
        /** @var PageModel $model */
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

                    if ($entity->getRedirectUrl()) {
                        return $this->redirect($entity->getRedirectUrl(), (int) $entity->getRedirectType());
                    } else {
                        return $this->notFound();
                    }
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
                                if ((string) $variantCookie !== (string) $entity->getId()) {
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
                    \assert($translatedEntity instanceof Page);

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
                $content = $entity->getContent();

                // Add the GA code to the template assets
                if (!empty($analytics)) {
                    $assetsHelper->addCustomDeclaration($analytics);
                }

                $logicalName = $themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/page.html.twig');

                $response = $this->render(
                    $logicalName,
                    [
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

            $event = new PageDisplayEvent((string) $content, $entity);
            $this->dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY);
            $content = $event->getContent();

            $model->hitPage($entity, $request, Response::HTTP_OK, $lead, $query);

            $response = new Response($content);
            if ($request->cookies->has('Blocked-Tracking')) {
                $deviceTrackingService->clearTrackingCookies();
            }

            return $response;
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
    public function previewAction(Request $request, CorePermissions $security, AnalyticsHelper $analyticsHelper, AssetsHelper $assetsHelper, ThemeHelper $themeHelper, int $id)
    {
        $contactId = (int) $request->query->get('contactId');

        if ($contactId) {
            /** @var LeadModel $leadModel */
            $leadModel = $this->getModel('lead.lead');
            /** @var Lead $contact */
            $contact = $leadModel->getEntity($contactId);
        }

        /** @var PageModel $model */
        $model = $this->getModel('page');
        /** @var Page $page */
        $page = $model->getEntity($id);

        if (!$page->getId()) {
            return $this->notFound();
        }

        $analytics = $analyticsHelper->getCode();

        $BCcontent = $page->getContent();
        $content   = $page->getCustomHtml();

        if (!$security->isAdmin()
            && (
                (!$page->isPublished())
                || (!$security->hasEntityAccess(
                    'page:pages:viewown',
                    'page:pages:viewother',
                    $page->getCreatedBy()
                )))
        ) {
            return $this->accessDenied();
        }

        if ($contactId && (
            !$security->isAdmin()
            || !$security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother')
        )
        ) {
            return $this->accessDenied();
        }

        if (empty($content) && !empty($BCcontent)) {
            $template = $page->getTemplate();
            // all the checks pass so display the content
            $content = $page->getContent();

            // Add the GA code to the template assets
            if (!empty($analytics)) {
                $assetsHelper->addCustomDeclaration($analytics);
            }

            $logicalName = $themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/page.html.twig');

            $response = $this->render(
                $logicalName,
                [
                    'content'  => $content,
                    'page'     => $page,
                    'template' => $template,
                    'public'   => true, // @deprecated Remove in 2.0
                ]
            );

            $content = $response->getContent();
        } else {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        if ($this->dispatcher->hasListeners(PageEvents::PAGE_ON_DISPLAY)) {
            $event = new PageDisplayEvent($content, $page, $this->getPreferenceCenterConfig());
            if (isset($contact) && $contact instanceof Lead) {
                $event->setLead($contact);
            }
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
        /** @var PageModel $model */
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
        ContactTracker $contactTracker,
    ) {
        $notSuccessResponse = new JsonResponse(
            [
                'success' => 0,
            ]
        );
        if (!$this->security->isAnonymous()) {
            return $notSuccessResponse;
        }

        /** @var PageModel $model */
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

        $sessionValue   = $trackingHelper->getCacheItem(true);

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
        $redirectId,
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

        $isHitTrackable = false;
        if ($ct) {
            if ($ipAddress->isTrackable()) {
                // Search replace lead fields in the URL
                /** @var LeadModel $leadModel */
                $leadModel = $this->getModel('lead');

                /** @var PageModel $pageModel */
                $pageModel = $this->getModel('page');

                try {
                    $lead           = $contactRequestHelper->getContactFromQuery(['ct' => $ct]);
                    $isHitTrackable = $pageModel->hitPage($redirect, $request, 200, $lead);
                } catch (InvalidDecodedStringException $e) {
                    // Invalid ct value so we must unset it
                    // and process the request without it

                    $logger->error(sprintf('Invalid clickthrough value: %s', $ct), ['exception' => $e]);

                    $request->request->set('ct', '');
                    $request->query->set('ct', '');
                    $lead           = $contactRequestHelper->getContactFromQuery();
                    $isHitTrackable = $pageModel->hitPage($redirect, $request, 200, $lead);
                }

                $leadArray            = ($lead) ? $primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($lead) : [];

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

        $response =  $this->redirect($url);
        $response->headers->setCookie(new Cookie('Blocked-Tracking', (string) !$isHitTrackable, strtotime('now + 15 seconds')));

        return $response;
    }

    /**
     * Track video views.
     */
    public function hitVideoAction(Request $request): JsonResponse|Response
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

    /**
     * @return array<string,bool>
     */
    private function getPreferenceCenterConfig(): array
    {
        return [
            'showContactFrequency'         => $this->coreParametersHelper->get('show_contact_frequency'),
            'showContactPauseDates'        => $this->coreParametersHelper->get('show_contact_pause_dates'),
            'showContactPreferredChannels' => $this->coreParametersHelper->get('show_contact_preferred_channels'),
            'showContactCategories'        => $this->coreParametersHelper->get('show_contact_categories'),
            'showContactSegments'          => $this->coreParametersHelper->get('show_contact_segments'),
        ];
    }
}
