<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\LeadBundle\EventListener\EmailSubscriber;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Mautic\PageBundle\Entity\Page;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PublicController
 */
class PublicController extends CommonFormController
{

    /**
     * @param         $slug
     * @param Request $request
     *
     * @return Response
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function indexAction($slug, Request $request)
    {
        //find the page
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model    = $this->getModel('page.page');
        $security = $this->factory->getSecurity();
        $entity   = $model->getEntityBySlugs($slug);

        if (!empty($entity)) {
            $published = $entity->isPublished();

            //make sure the page is published or deny access if not
            if ((!$published) && (!$security->hasEntityAccess('page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()))) {
                //If the page has a redirect type, handle it
                if ($entity->getRedirectType() != null) {
                    return $this->redirect($entity->getRedirectUrl(), $entity->getRedirectType());
                } else {
                    $model->hitPage($entity, $this->request, 401);

                    return $this->accessDenied();
                }
            }

            if ($request->attributes->has('ignore_mismatch')) {
                //make sure URLs match up
                $url        = $model->generateUrl($entity, false);
                $requestUri = $this->request->getRequestUri();

                //remove query
                $query = $this->request->getQueryString();
                if (!empty($query)) {
                    $requestUri = str_replace("?{$query}", '', $url);
                }

                //redirect if they don't match
                if ($requestUri != $url) {
                    $model->hitPage($entity, $this->request, 301);

                    return $this->redirect($url, 301);
                }
            }

            //check for variants
            $parentVariant   = $entity->getVariantParent();
            $childrenVariant = $entity->getVariantChildren();

            $userAccess = $security->hasEntityAccess('page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy());

            //is this a variant of another? If so, the parent URL should be used unless a user is logged in and previewing
            if ($parentVariant && !$userAccess) {
                $model->hitPage($entity, $this->request, 301);
                $url = $model->generateUrl($parentVariant, false);

                return $this->redirect($url, 301);
            }

            if (!$userAccess) {
                //check to see if a variant should be shown versus the parent but ignore if a user is previewing
                if (count($childrenVariant)) {

                    $variants      = [];
                    $variantWeight = 0;
                    $totalHits     = $entity->getVariantHits();
                    foreach ($childrenVariant as $id => $child) {
                        if ($child->isPublished()) {
                            $variantSettings = $child->getVariantSettings();
                            $variants[$id]   = [
                                'weight' => ($variantSettings['weight'] / 100),
                                'hits'   => $child->getVariantHits()
                            ];
                            $variantWeight += $variantSettings['weight'];
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
                                    $entity = $childrenVariant[$variantCookie];
                                } //otherwise proceed with displaying parent
                            }
                        } else {
                            //add parent weight
                            $variants[$entity->getId()] = [
                                'weight' => ((100 - $variantWeight) / 100),
                                'hits'   => $entity->getVariantHits()
                            ];

                            //determine variant to show
                            $byWeight = [];
                            foreach ($variants as $id => $v) {
                                $byWeight[$id] = ($totalHits) ? ($v['hits'] / $totalHits) - $v['weight'] : 0;
                            }

                            //find the one with the most difference from weight
                            $greatestDiff = min($byWeight);
                            $useId        = array_search($greatestDiff, $byWeight);

                            //set the cookie - 14 days
                            $this->factory->getHelper('cookie')->setCookie('mautic_page_'.$entity->getId(), $useId, 3600 * 24 * 14);

                            if ($useId != $entity->getId()) {
                                $entity = $childrenVariant[$useId];
                            }
                        }
                    }
                }
            }
            //let's check for preferred languages if we have a multi-language group of pages
            $translationParent   = $entity->getTranslationParent();
            $translationChildren = $entity->getTranslationChildren();
            if ($translationParent || count($translationChildren)) {
                $session = $this->factory->getSession();
                if ($translationParent) {
                    $translationChildren = $translationParent->getTranslationChildren();
                } else {
                    $translationParent = $entity;
                }

                //check to see if this group has already been redirected
                $doNotRedirect = $session->get('mautic.page.'.$translationParent->getId().'.donotredirect', false);

                if (empty($doNotRedirect)) {
                    $session->set('mautic.page.'.$translationParent->getId().'.donotredirect', 1);

                    //generate a list of translations
                    $langs = [$translationParent->getLanguage()];
                    foreach ($translationChildren as $c) {
                        $langs[$c->getId()] = $c->getLanguage();
                    }

                    //loop through the translations to ensure there is a generic option for each
                    //dialect (i.e en if en_US is present)
                    $pageLangs = [];
                    $pageIds   = [];
                    foreach ($langs as $id => $l) {
                        $pageIds[]   = $id;
                        $pageLangs[] = $l;
                        if (strpos($l, '_') !== false) {
                            $base = substr($l, 0, 2);
                            if (!in_array($base, $pageLangs)) {
                                $pageLangs[] = $base;
                                $pageIds[]   = $id;
                            }
                        }
                    }

                    //get the browser preferred languages
                    $browserLangs = $this->request->server->get('HTTP_ACCEPT_LANGUAGE');
                    if (!empty($browserLangs)) {
                        $langs = explode(',', $browserLangs);
                        if (!empty($langs)) {
                            foreach ($langs as $k => $l) {
                                if ($pos = strpos($l, ';q=') !== false) {
                                    //remove weights
                                    $l = substr($l, 0, ($pos + 1));
                                }
                                //change - to _
                                $langs[$k] = str_replace('-', '_', $l);
                            }
                        }

                        //loop through the browser languages to ensure there is a generic option for each
                        //dialect (i.e en if en_US is present)
                        $userLangs = [];
                        foreach ($langs as $k => $l) {
                            $userLangs[] = $l;

                            if (strpos($l, '_') !== false) {
                                $base = substr($l, 0, 2);
                                if (!in_array($base, $langs) && !in_array($base, $userLangs)) {
                                    $userLangs[] = $base;
                                }
                            }
                        }

                        //get translations in order of browser preference
                        $matches = array_intersect($userLangs, $pageLangs);
                        if (!empty($matches)) {
                            $preferred = reset($matches);
                            $key       = array_search($preferred, $pageLangs);
                            $pageId    = $pageIds[$key];

                            //redirect if not already on the correct page
                            if ($pageId && $pageId != $entity->getId()) {
                                $page = ($pageId == $translationParent->getId()) ? $translationParent : $translationChildren[$pageId];
                                if ($page !== null) {
                                    $url = $model->generateUrl($page, false);
                                    $model->hitPage($entity, $this->request, 302);

                                    return $this->redirect($url, 302);
                                }
                            }
                        }
                    }
                }
            }

            $analytics = $this->factory->getHelper('template.analytics')->getCode();

            $BCcontent = $entity->getContent();
            $content = $entity->getCustomHtml();
            // This condition remains so the Mautic v1 themes would display the content
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
                        'public'   => true
                    ]
                );

                $content = $response->getContent();
            } else {
                if (!empty($analytics)) {
                    $content = str_replace('</head>', $analytics."\n</head>", $content);
                }
            }

            $event = new PageDisplayEvent($content, $entity);
            $this->factory->getDispatcher()->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
            $content = $event->getContent();

            $model->hitPage($entity, $this->request, 200);

            return new Response($content);
        }

        $model->hitPage($entity, $this->request, 404);

        $this->notFound();
    }

    /**
     * @param $id
     *
     * @return Response|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function previewAction($id)
    {
        $model  = $this->getModel('page');
        $entity = $model->getEntity($id);

        if ($entity === null || !$entity->isPublished(false)) {
            $this->notFound();
        }

        $analytics = $this->factory->getHelper('template.analytics')->getCode();

        $BCcontent = $entity->getContent();
        $content = $entity->getCustomHtml();
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
                    'public'   => true // @deprecated Remove in 2.0
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
        //Create page entry
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page');
        $model->hitPage(null, $this->request);

        return TrackingPixelHelper::getResponse($this->request);
    }

    /**
     * @param $redirectId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function redirectAction($redirectId)
    {
        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->getModel('page.redirect');
        $redirect      = $redirectModel->getRedirectById($redirectId);

        if (empty($redirect) || !$redirect->isPublished(false)) {
            throw $this->createNotFoundException($this->factory->getTranslator()->trans('mautic.core.url.error.404'));
        }

        $this->getModel('page')->hitPage($redirect, $this->request);

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
                        'keyboard'         => true
                    ];
                }

                // Create sample slides for first time or if all slides were deleted
                if (empty($options['slides'])) {
                    $options['slides'] = [
                        [
                            'order'            => 0,
                            'background-image' => $assetsHelper->getUrl('media/images/mautic_logo_lb200.png'),
                            'captionheader'    => 'Caption 1'
                        ],
                        [
                            'order'            => 1,
                            'background-image' => $assetsHelper->getUrl('media/images/mautic_logo_db200.png'),
                            'captionheader'    => 'Caption 2'
                        ]
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
                $value = isset($content[$slot]) ? nl2br($content[$slot]) : "";
                $slotsHelper->set($slot, $value);
            } else {
                // Fallback for other types like html, text, textarea and all unknown
                $value = isset($content[$slot]) ? $content[$slot] : "";
                $slotsHelper->set($slot, $value);
            }
        }

        $parentVariant = $entity->getVariantParent();
        $title         = (!empty($parentVariant)) ? $parentVariant->getTitle() : $entity->getTitle();
        $slotsHelper->set('pageTitle', $title);
    }
}
