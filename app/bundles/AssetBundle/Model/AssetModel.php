<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\AssetEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class AssetModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class AssetModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (empty($this->inConversion)) {
            $alias = $entity->getAlias();
            if (empty($alias)) {
                $alias = strtolower(InputHelper::alphanum($entity->getTitle(), true));
            } else {
                $alias = strtolower(InputHelper::alphanum($alias, true));
            }

            //make sure alias is not already taken
            $repo      = $this->getRepository();
            $testAlias = $alias;
            $count     = $repo->checkUniqueAlias($testAlias, $entity);
            $aliasTag  = $count;

            while ($count) {
                $testAlias = $alias . $aliasTag;
                $count     = $repo->checkUniqueAlias($testAlias, $entity);
                $aliasTag++;
            }
            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        $now = new \DateTime();

        //set the author for new asset
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

    public function getRepository()
    {
        return $this->em->getRepository('MauticAssetBundle:Asset');
    }

    public function getPermissionBase()
    {
        return 'asset:assets';
    }

    public function getNameGetter()
    {
        return "getTitle";
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
        if (!$entity instanceof Asset) {
            throw new MethodNotAllowedHttpException(array('Asset'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('asset', $entity, $params);
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
            $entity = new Asset();
            $entity->setSessionId('new_' . uniqid());
        } else {
            $entity = parent::getEntity($id);
            $entity->setSessionId($entity->getId());
        }

        return $entity;
    }

// TODO (@Jan): I have a feeling that following commented methods will be needed

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    // protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    // {
    //     if (!$entity instanceof Page) {
    //         throw new MethodNotAllowedHttpException(array('Page'));
    //     }

    //     switch ($action) {
    //         case "pre_save":
    //             $name = PageEvents::PAGE_PRE_SAVE;
    //             break;
    //         case "post_save":
    //             $name = PageEvents::PAGE_POST_SAVE;
    //             break;
    //         case "pre_delete":
    //             $name = PageEvents::PAGE_PRE_DELETE;
    //             break;
    //         case "post_delete":
    //             $name = PageEvents::PAGE_POST_DELETE;
    //             break;
    //         default:
    //             return false;
    //     }

    //     if ($this->dispatcher->hasListeners($name)) {
    //         if (empty($event)) {
    //             $event = new PageEvent($entity, $isNew);
    //             $event->setEntityManager($this->em);
    //         }

    //         $this->dispatcher->dispatch($name, $event);
    //         return $event;
    //     } else {
    //         return false;
    //     }
    // }

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
            case 'asset':
                $viewOther = $this->security->isGranted('asset:assets:viewother');
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->factory->getUser());
                $results = $repo->getAssetList($filter, $limit, 0, $viewOther);
                break;
            case 'category':
                $results = $this->factory->getModel('category.category')->getRepository()->getCategoryList($filter, $limit, 0);
                break;
        }

        return $results;
    }

    /**
     * Generate url for a page
     *
     * @param $entity
     * @param $absolute
     * @return mixed
     */
    public function generateUrl($entity, $absolute = true)
    {
        $assetSlug = $entity->getId() . ':' . $entity->getAlias();

        $slugs = array(
            'slug' => $assetSlug
        );

        $assetUrl  = $this->factory->getRouter()->generate('mautic_asset_download', $slugs, $absolute);

        return $assetUrl;
    }
}