<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\PageBundle\Entity\Redirect;

/**
 * Class RedirectModel
 */
class RedirectModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\PageBundle\Entity\RedirectRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPageBundle:Redirect');
    }

    /**
     * @param Redirect $redirect
     * @param array    $clickthrough
     *
     * @return string
     */
    public function generateRedirectUrl(Redirect $redirect, $clickthrough = array())
    {
        $url  = $this->buildUrl('mautic_page_trackable', array('redirectId' => $redirect->getRedirectId()), true, $clickthrough);

        return $url;
    }

    /**
     * @param      $url
     * @param null $forEmail
     * @param bool $createEntity
     *
     * @return Redirect|null
     */
    public function getRedirectByUrl($url, $forEmail = null, $createEntity = true)
    {
        $repo     = $this->getRepository();
        $criteria = array('url' => $url);

        $criteria['email'] = $forEmail;

        $redirect = $repo->findOneBy($criteria);

        if ($redirect == null && $createEntity) {
            $redirect = new Redirect();
            $redirect->setUrl($url);
            $redirect->setEmail($forEmail);

            $redirect->setRedirectId();
            $this->setTimestamps($redirect, true);
        }

        return $redirect;
    }

    /**
     * @param      $urls
     * @param null $forEmail
     * @param bool $createEntity
     *
     * @return array
     */
    public function getRedirectListByUrls($urls, $forEmail = null, $createEntity = true)
    {
        $repo      = $this->getRepository();
        $redirects = $repo->findByUrls(array_values($urls), $forEmail);

        $byUrl = array();
        foreach ($redirects as $redirect) {
            $byUrl[$redirect->getUrl()] = $redirect;
        }

        $return = array();
        foreach ($urls as $key => $url) {
            if (isset($byUrl[$url])) {
                $return[$key] = $byUrl[$url];
            } elseif ($createEntity) {
                $redirect = new Redirect();
                $redirect->setUrl($url);
                $redirect->setEmail($forEmail);
                $redirect->setRedirectId();
                $this->setTimestamps($redirect, true);

                $return[$key] = $redirect;
            }
        }

        unset($redirects, $byUrl);

        return $return;
    }

    /**
     * @param      $ids
     * @param null $forEmail
     *
     * @return array
     */
    public function getRedirectListByIds($ids, $forEmail = null)
    {
        $repo      = $this->getRepository();
        $redirects = $repo->findByIds(array_values($ids), $forEmail);

        $byId = array();
        foreach ($redirects as $redirect) {
            $byId[$redirect->getRedirectId()] = $redirect;
        }

        $return = array();
        foreach ($ids as $key => $id) {
            if (isset($byId[$id])) {
                $return[$key] = $byId[$id];
            }
        }

        unset($redirects, $byId);

        return $return;
    }

    /**
     * @param $identifier
     *
     * @return null|Redirect
     */
    public function getRedirectById($identifier)
    {
        return $this->getRepository()->findOneBy(array('redirectId' => $identifier));
    }

    /**
     * @param $source
     * @param $id
     *
     * @return mixed
     */
    public function getRedirectListBySource($source, $id)
    {
        return $this->getRepository()->findBySource($source, $id);
    }

}
