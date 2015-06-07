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
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class PublicController
 */
class PublicController extends CommonFormController
{

    /**
     * @param string $slug
     *
     * @return Response|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws AccessDeniedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function indexAction($slug, Request $request)
    {
        //find the page
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model      = $this->factory->getModel('page.page');
        $security   = $this->factory->getSecurity();
        $translator = $this->get('translator');
        $entity     = $model->getEntityBySlugs($slug);

        if (!empty($entity)) {
            $published = $entity->isPublished();

            //make sure the page is published or deny access if not
            if ((!$published) && (!$security->hasEntityAccess('page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()))) {
                $model->hitPage($entity, $this->request, 401);
                throw new AccessDeniedHttpException($translator->trans('mautic.core.url.error.401'));
            }

            if ($request->attributes->has('ignore_mismatch')) {
                //make sure URLs match up
                $url        = $model->generateUrl($entity, false);
                $requestUri = $this->request->getRequestUri();

                //remove query
                $query      = $this->request->getQueryString();
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

            $userAccess      = $security->hasEntityAccess('page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy());

            //is this a variant of another? If so, the parent URL should be used unless a user is logged in and previewing
            if ($parentVariant && !$userAccess) {
                $model->hitPage($entity, $this->request, 301);
                $url = $model->generateUrl($parentVariant, false);
                return $this->redirect($url, 301);
            }

            if (!$userAccess) {
                //check to see if a variant should be shown versus the parent but ignore if a user is previewing
                if (count($childrenVariant)) {

                    $variants      = array();
                    $variantWeight = 0;
                    $totalHits     = $entity->getVariantHits();
                    foreach ($childrenVariant as $id => $child) {
                        if ($child->isPublished()) {
                            $variantSettings = $child->getVariantSettings();
                            $variants[$id]   = array(
                                'weight' => ($variantSettings['weight'] / 100),
                                'hits'   => $child->getVariantHits()
                            );
                            $variantWeight += $variantSettings['weight'];
                            $totalHits     += $variants[$id]['hits'];
                        }
                    }

                    if (count($variants)) {
                        //check to see if this user has already been displayed a specific variant
                        $variantCookie = $this->request->cookies->get('mautic_page_' . $entity->getId());

                        if (!empty($variantCookie)) {
                            if (isset($variants[$variantCookie])) {
                                //if not the parent, show the specific variant already displayed to the visitor
                                if ($variantCookie !== $entity->getId()) {
                                    $entity = $childrenVariant[$variantCookie];
                                } //otherwise proceed with displaying parent
                            }
                        } else {
                            //add parent weight
                            $variants[$entity->getId()] = array(
                                'weight' => ((100 - $variantWeight)/100),
                                'hits'   => $entity->getVariantHits()
                            );

                            //determine variant to show
                            $byWeight = array();
                            foreach ($variants as $id => $v) {
                                $byWeight[$id] = ($totalHits) ? ($v['hits'] / $totalHits) - $v['weight'] : 0;
                            }

                            //find the one with the most difference from weight
                            $greatestDiff = min($byWeight);
                            $useId        = array_search($greatestDiff, $byWeight);

                            //set the cookie - 14 days
                            $this->factory->getHelper('cookie')->setCookie('mautic_page_' . $entity->getId(), $useId, 3600 * 24 * 14);

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
                    $langs    = array($translationParent->getLanguage());
                    foreach ($translationChildren as $c) {
                        $langs[$c->getId()] = $c->getLanguage();
                    }

                    //loop through the translations to ensure there is a generic option for each
                    //dialect (i.e en if en_US is present)
                    $pageLangs = array();
                    $pageIds   = array();
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
                        $userLangs = array();
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

            $template = $entity->getTemplate();
            if (!empty($template)) {
                //all the checks pass so display the content
                $slots    = $this->factory->getTheme($template)->getSlots('page');
                $response = $this->render('MauticPageBundle::public.html.php', array(
                    'slots'           => $slots,
                    'content'         => $entity->getContent(),
                    'page'            => $entity,
                    'template'        => $template,
                    'public'          => true
                ));

                $content = $response->getContent();
            } else {
                $content = $entity->getCustomHtml();
                $analytics = $this->factory->getParameter('google_analytics');
                if (!empty($analytics)) {
                    $content = str_replace('</head>', htmlspecialchars_decode($analytics) . "\n</head>", $content);
                }
            }

            $event = new PageDisplayEvent($content, $entity);
            $this->factory->getDispatcher()->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
            $content = $event->getContent();

            $model->hitPage($entity, $this->request, 200);

            return new Response($content);
        }

        $model->hitPage($entity, $this->request, 404);
        throw $this->createNotFoundException($translator->trans('mautic.core.url.error.404'));
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
        $model      = $this->factory->getModel('page');
        $entity     = $model->getEntity($id);
        $translator = $this->get('translator');

        if ($entity === null || !$entity->isPublished(false)) {
            throw $this->createNotFoundException($translator->trans('mautic.core.url.error.404'));
        }

        $template = $entity->getTemplate();
        if (!empty($template)) {
            //all the checks pass so display the content
            $slots    = $this->factory->getTheme($template)->getSlots('page');
            $response = $this->render('MauticPageBundle::public.html.php', array(
                'slots'           => $slots,
                'content'         => $entity->getContent(),
                'page'            => $entity,
                'template'        => $template,
                'public'          => true
            ));

            $content = $response->getContent();
        } else {
            $content = $entity->getCustomHtml();
            $analytics = $this->factory->getParameter('google_analytics');
            if (!empty($analytics)) {
                $content = str_replace('</head>', htmlspecialchars_decode($analytics) . "\n</head>", $content);
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
        $response = TrackingPixelHelper::getResponse($this->request);

        //Create page entry
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model   = $this->factory->getModel('page');
        $model->hitPage(null, $this->request);

        return $response;
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
        $redirectModel = $this->factory->getModel('page.redirect');
        $redirect      = $redirectModel->getRedirectById($redirectId);

        if (empty($redirect)) {
            throw $this->createNotFoundException($this->factory->getTranslator()->trans('mautic.core.url.error.404'));
        }

        $this->factory->getModel('page')->hitPage($redirect, $this->request);

        return $this->redirect($redirect->getUrl());
    }
}
