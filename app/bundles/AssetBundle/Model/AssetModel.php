<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Model;

use Mautic\AssetBundle\Event\AssetEvent;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\AssetEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class AssetModel
 */
class AssetModel extends FormModel
{
    /**
     * {@inheritdoc}
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

        //set the author for new asset
        if (!$entity->isNew()) {
            //increase the revision
            $revision = $entity->getRevision();
            $revision++;
            $entity->setRevision($revision);
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * @param        $asset
     * @param        $request
     * @param string $code
     */
    public function trackDownload($asset, $request, $code = '200')
    {
        $download = new Download();
        $download->setDateDownload(new \Datetime());

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        //check for any clickthrough info
        $clickthrough = $request->get('ct', false);
        if (!empty($clickthrough)) {
            $clickthrough = unserialize(base64_decode($clickthrough));

            if (!empty($clickthrough['lead'])) {
                $lead = $leadModel->getEntity($clickthrough['lead']);
                if ($lead !== null) {
                    $leadModel->setLeadCookie($clickthrough['lead']);
                    list($trackingId, $generated) = $leadModel->getTrackingCookie();
                    $leadClickthrough = true;
                }
            }

            if (!empty($clickthrough['source'])) {
                $download->setSource($clickthrough['source'][0]);
                $download->setSourceId($clickthrough['source'][1]);
            }
        }

        if (empty($leadClickthrough)) {
            list($lead, $trackingId, $generated) = $leadModel->getCurrentLead(true);
        }

        $download->setLead($lead);
        $download->setTrackingId($trackingId);

        if (!empty($asset)) {
            $download->setAsset($asset);

            $downloadCount = $asset->getDownloadCount();
            $downloadCount++;
            $asset->setDownloadCount($downloadCount);

            //check for a download count from tracking id
            $countById = $this->em
                ->getRepository('MauticAssetBundle:Download')->getDownloadCountForTrackingId($asset->getId(), $trackingId);
            if (empty($countById)) {
                $uniqueDownloadCount = $asset->getUniqueDownloadCount();
                $uniqueDownloadCount++;
                $asset->setUniqueDownloadCount($uniqueDownloadCount);
            }

            $this->em->persist($asset);
        }

        //check for existing IP
        $ip = $request->server->get('REMOTE_ADDR');
        $ipAddress = $this->em->getRepository('MauticCoreBundle:IpAddress')
            ->findOneByIpAddress($ip);

        if ($ipAddress === null) {
            $ipAddress = new IpAddress();
            $ipAddress->setIpAddress($ip, $this->factory->getSystemParameters());
        }

        $download->setCode($code);
        $download->setIpAddress($ipAddress);
        $download->setReferer($request->server->get('HTTP_REFERER'));

        $this->em->persist($download);
        $this->em->flush();
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
     * @throws NotFoundHttpException
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
        } else {
            $entity = parent::getEntity($id);
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
        if (!$entity instanceof Asset) {
            throw new MethodNotAllowedHttpException(array('Asset'));
        }

        switch ($action) {
            case "pre_save":
                $name = AssetEvents::ASSET_PRE_SAVE;
                break;
            case "post_save":
                $name = AssetEvents::ASSET_POST_SAVE;
                break;
            case "pre_delete":
                $name = AssetEvents::ASSET_PRE_DELETE;
                break;
            case "post_delete":
                $name = AssetEvents::ASSET_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new AssetEvent($entity, $isNew);
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
     *
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
     * Generate url for an asset
     *
     * @param Asset $entity
     * @param bool  $absolute
     * @param array $clickthrough
     *
     * @return string
     */
    public function generateUrl($entity, $absolute = true, $clickthrough = array())
    {
        $assetSlug = $entity->getId() . ':' . $entity->getAlias();

        $slugs = array(
            'slug' => $assetSlug
        );

        $assetUrl  = $this->factory->getRouter()->generate('mautic_asset_download', $slugs, $absolute);
        $assetUrl .= (!empty($clickthrough)) ? '?ct=' . base64_encode(serialize($clickthrough)) : '';

        return $assetUrl;
    }
}
