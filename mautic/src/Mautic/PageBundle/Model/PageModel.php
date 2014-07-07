<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\PageBundle\Entity\Analytics;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class PageModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class PageModel extends FormModel
{

    public function getRepository()
    {
        return $this->em->getRepository('MauticPageBundle:Page');
    }

    public function getPermissionBase()
    {
        return 'page:pages';
    }

    public function getNameGetter()
    {
        return "getTitle";
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = strtolower(InputHelper::alphanum($entity->getTitle(), true));
        } else {
            $alias = strtolower(InputHelper::alphanum($alias, true));
        }

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $count     = $repo->checkUniqueAlias($testAlias, $entity->getId());
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias . $aliasTag;
            $count     = $repo->checkUniqueAlias($testAlias, $entity->getId());
            $aliasTag++;
        }
        if ($testAlias != $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        //set the author for new pages
        if ($entity->isNew()) {
            $user = $this->factory->getUser();
            $entity->setAuthor($user->getName());
        } else {
            //increase the revision
            $revision = $entity->getRevision();
            $revision++;
            $entity->setRevision($revision);
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(array('Page'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('page', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            $entity = new Page();
            $entity->setSessionId('new_' . uniqid());
        } else {
            $entity = parent::getEntity($id);
            $entity->setSessionId($entity->getId());
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(array('Page'));
        }

        switch ($action) {
            case "pre_save":
                $name = PageEvents::PAGE_PRE_SAVE;
                break;
            case "post_save":
                $name = PageEvents::PAGE_POST_SAVE;
                break;
            case "pre_delete":
                $name = PageEvents::PAGE_PRE_DELETE;
                break;
            case "post_delete":
                $name = PageEvents::PAGE_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PageEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return false;
        }
    }

    /**
     * Get list of entities for autopopulate fields
     *
     * @param $type
     * @param $filter
     * @param $limit
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = array();
        switch ($type) {
            case 'page':
                $viewOther = $this->security->isGranted('page:pages:viewother');
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->factory->getUser());
                $results = $repo->getPageList($filter, $limit, 0, $viewOther);
                break;
            case 'category':
                $results = $this->factory->getModel('page.category')->getRepository()->getCategoryList($filter, $limit, 0);
                break;
        }

        return $results;
    }

    /**
     * Generate url for a page
     *
     * @param $entity
     * @return mixed
     */
    public function generateUrl($entity)
    {
        $pageSlug = $entity->getId() . ':' . $entity->getAlias();

        //should the url include the category
        $catInUrl    = $this->factory->getParam('cat_in_page_url');
        if ($catInUrl) {
            $category = $entity->getCategory();
            $catSlug = (!empty($category)) ? $category->getId() . ':' . $category->getAlias() :
                $this->translator->trans('mautic.core.url.uncategorized');
        }

        $pageUrl  = $this->factory->getRouter()->generate('mautic_page_public', array(
            'slug1' => (!empty($catSlug)) ? $catSlug : $pageSlug,
            'slug2' => (!empty($catSlug)) ? $pageSlug : ''
        ), true);

        return $pageUrl;
    }

    public function hitPage($page, $request, $code = '200')
    {
        $hit = new Analytics();
        $hit->setDateHit(new \Datetime());
        if (!empty($page)) {
            $hit->setPage($page);

            $hitCount = $page->getHits();
            $hitCount++;
            $page->setHits($hitCount);
            $this->em->persist($page);
        }

        //check for existing IP
        $ip = $request->server->get('REMOTE_ADDR');
        $ipAddress = $this->em->getRepository('MauticCoreBundle:IpAddress')
            ->findOneByIpAddress($ip);

        if ($ipAddress === null) {
            $ipAddress = new IpAddress();
            $ipAddress->setIpAddress($ip, $this->factory->getSystemParameters());
        }

        $hit->setIpAddress($ipAddress);

        //gleam info from the IP address
        if ($details = $ipAddress->getIpDetails()) {
            $hit->setCountry($details['country']);
            $hit->setRegion($details['region']);
            $hit->setCity($details['city']);
            $hit->setIsp($details['isp']);
            $hit->setOrganization($details['organization']);
        }

        $hit->setCode($code);
        $hit->setReferer($request->server->get('HTTP_REFERER'));
        $hit->setUserAgent($request->server->get('HTTP_USER_AGENT'));
        $hit->setRemoteHost($request->server->get('REMOTE_HOST'));

        $pageURL = 'http';
        if ($request->server->get("HTTPS") == "on") {$pageURL .= "s";}
        $pageURL .= "://";
        if ($request->server->get("SERVER_PORT") != "80") {
            $pageURL .= $request->server->get("SERVER_NAME").":".$request->server->get("SERVER_PORT").
                $request->server->get("REQUEST_URI");
        } else {
            $pageURL .= $request->server->get("SERVER_NAME").$request->server->get("REQUEST_URI");
        }
        $hit->setUrl($pageURL);

        //check for the tracking cookie
        $trackingId = $request->cookies->get('mautic.analytics.id');
        if (empty($trackingId)) {
            $trackingId = uniqid();
        }
        //create a tracking cookie
        $expire = time() + 1800;
        setcookie('mautic.analytics.id', $trackingId, $expire);
        $hit->setTrackingId($trackingId);

        $this->em->persist($hit);
        $this->em->flush();
    }
}