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
        $router = $this->factory->getRouter();

        $url  = $router->generate('mautic_page_redirect', array('redirectId' => $redirect->getRedirectId()), true);
        $r    = urlencode($redirect->getUrl());
        $url .= (!empty($clickthrough)) ? '?ct=' . $this->encodeArrayForUrl($clickthrough) . '&r=' . $r : '?r=' . $r;

        return $url;
    }

    /**
     * @param      $identifier
     * @param bool $byUrl
     * @param bool $createEntity
     *
     * @return Redirect
     */
    public function getRedirect($identifier, $byUrl = true, $createEntity = true)
    {
        $repo     = $this->getRepository();
        $criteria = ($byUrl) ? array('url' => $identifier) : array('redirectId' => $identifier);
        $redirect = $repo->findOneBy($criteria);

        if ($byUrl && $redirect == null && $createEntity) {
            $redirect = new Redirect();
            $redirect->setUrl($identifier);
            $redirect->setRedirectId(hash('sha1', uniqid(mt_rand())));
            $this->setTimestamps($redirect, true);
            $repo->saveEntity($redirect);
        }

        return $redirect;
    }
}
