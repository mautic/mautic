<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Model;

use Doctrine\ORM\PersistentCollection;
use Mautic\AssetBundle\Event\AssetEvent;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\AssetEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;
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
                $alias = strtolower(InputHelper::alphanum($entity->getTitle(), false, '-'));
            } else {
                $alias = strtolower(InputHelper::alphanum($alias, false, '-'));
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
     * @param array  $systemEntry
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function trackDownload($asset, $request = null, $code = '200', $systemEntry = array())
    {
        //don't skew results with in-house downloads
        if (empty($systemEntry) && !$this->factory->getSecurity()->isAnonymous()) {
            return;
        }

        if ($request == null) {
            $request = $this->factory->getRequest();
        }

        $download = new Download();
        $download->setDateDownload(new \Datetime());

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        // Download triggered by lead
        if (empty($systemEntry)) {

            //check for any clickthrough info
            $clickthrough = $request->get('ct', false);
            if (!empty($clickthrough)) {
                $clickthrough = $this->decodeArrayFromUrl($clickthrough);

                if (!empty($clickthrough['lead'])) {
                    $lead = $leadModel->getEntity($clickthrough['lead']);
                    if ($lead !== null) {
                        $leadModel->setLeadCookie($clickthrough['lead']);
                        list($trackingId, $generated) = $leadModel->getTrackingCookie();
                        $leadClickthrough = true;

                        $leadModel->setCurrentLead($lead);
                    }
                }

                if (!empty($clickthrough['source'])) {
                    $download->setSource($clickthrough['source'][0]);
                    $download->setSourceId($clickthrough['source'][1]);
                }

                if (!empty($clickthrough['email'])) {
                    $download->setEmail($this->em->getReference('MauticEmailBundle:Email', $clickthrough['email']));
                }
            }

            if (empty($leadClickthrough)) {
                list($lead, $trackingId, $generated) = $leadModel->getCurrentLead(true);
            }

            $download->setLead($lead);
        } else {
            $trackingId = '';

            if (isset($systemEntry['lead'])) {
                $lead = $systemEntry['lead'];
                if (!$lead instanceof Lead) {
                    $leadId = is_array($lead) ? $lead['id'] : $lead;
                    $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
                }

                $download->setLead($lead);
            }

            if (!empty($systemEntry['source'])) {
                $download->setSource($systemEntry['source'][0]);
                $download->setSourceId($systemEntry['source'][1]);
            }

            if (isset($systemEntry['email'])) {
                $email = $systemEntry['email'];
                if (!$email instanceof Email) {
                    $emailId = is_array($email) ? $email['id'] : $email;
                    $email   = $this->em->getReference('MauticEmailBundle:Email', $emailId);
                }

                $download->setEmail($email);
            }

            if (isset($systemEntry['tracking_id'])) {
                $trackingId = $systemEntry['tracking_id'];
            } elseif ($this->factory->getSecurity()->isAnonymous() && !defined('IN_MAUTIC_CONSOLE')) {
                // If the session is anonymous and not triggered via CLI, assume the lead did something to trigger the
                // system forced download such as an email
                list($trackingId, $generated) = $leadModel->getTrackingCookie();
            }
        }

        $download->setTrackingId($trackingId);

        if (!empty($asset) && empty($systemEntry)) {
            $download->setAsset($asset);

            $downloadCount = $asset->getDownloadCount();
            $downloadCount++;
            $asset->setDownloadCount($downloadCount);

            //check for a download count from tracking id
            $countById = $this->getDownloadRepository()->getDownloadCountForTrackingId($asset->getId(), $trackingId);
            if (empty($countById)) {
                $uniqueDownloadCount = $asset->getUniqueDownloadCount();
                $uniqueDownloadCount++;
                $asset->setUniqueDownloadCount($uniqueDownloadCount);
            }

            $this->em->persist($asset);
        }

        //check for existing IP
        $ipAddress = $this->factory->getIpAddress();

        $download->setCode($code);
        $download->setIpAddress($ipAddress);
        $download->setReferer($request->server->get('HTTP_REFERER'));

        $this->em->persist($download);

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->flush();
        } catch (\Exception $e) {
            error_log($e);
        }
    }

    /**
     * Increase the download count
     *
     * @param            $asset
     * @param int        $increaseBy
     * @param bool|false $unique
     */
    public function upDownloadCount($asset, $increaseBy = 1, $unique = false)
    {
        $id = ($asset instanceof Asset) ? $asset->getId() : (int) $asset;

        $this->getRepository()->upDownloadCount($id, $increaseBy, $unique);
    }

    /**
     * @return \Mautic\AssetBundle\Entity\AssetRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticAssetBundle:Asset');
    }

    /**
     * @return \Mautic\AssetBundle\Entity\DownloadRepository
     */
    public function getDownloadRepository()
    {
        return $this->em->getRepository('MauticAssetBundle:Download');
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'asset:assets';
    }

    /**
     * @return string
     */
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
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
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
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new AssetEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return null;
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

        return $this->buildUrl('mautic_asset_download', $slugs, $absolute, $clickthrough);
    }

    /**
     * Determine the max upload size based on PHP restrictions and config
     */
    public function getMaxUploadSize()
    {
        $maxAssetSize  = Asset::convertSizeToBytes($this->factory->getParameter('max_size') . 'M');
        $maxPostSize   = Asset::convertSizeToBytes(ini_get('post_max_size'));
        $maxUploadSize = Asset::convertSizeToBytes(ini_get('upload_max_filesize'));
        $memoryLimit   = Asset::convertSizeToBytes(ini_get('memory_limit'));

        $maxAllowed    =  min(array_filter(array($maxAssetSize, $maxPostSize, $maxUploadSize, $memoryLimit)));

        return round($maxAllowed / 1048576, 2);
    }

    /**
     * @param $assets
     *
     * @return int|string
     */
    public function getTotalFilesize($assets)
    {
        $firstAsset = is_array($assets) ? reset($assets) : false;
        if ($assets instanceof PersistentCollection || is_object($firstAsset)) {
            $assetIds = array();
            foreach ($assets as $asset) {
                $assetIds[] = $asset->getId();
            }
            $assets = $assetIds;
        }

        if (!is_array($assets)) {
            $assets = array($assets);
        }

        if (empty($assets)) {
            return 0;
        }

        $repo = $this->getRepository();
        $size = $repo->getAssetSize($assets);

        if ($size) {
            $size = Asset::convertBytesToHumanReadable($size);
        }

        return $size;
    }
}
