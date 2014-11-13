<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\PageBundle\Entity\Redirect;

/**
 * Class RedirectModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class RedirectModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Doctrine\ORM\EntityRepository|\Mautic\CoreBundle\Entity\CommonRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPageBundle:Redirect');
    }

    /**
     * @param $redirect
     * @param $clickthrough
     */
    public function generateRedirectUrl(Redirect $redirect, $clickthrough = array())
    {
        $router = $this->factory->getRouter();

        $url  = $router->generate('mautic_page_redirect', array('redirectId' => $redirect->getRedirectId()), true);
        $r    = urlencode($redirect->getUrl());
        $url .= (!empty($clickthrough)) ? '?ct=' . base64_encode(serialize($clickthrough)) . '&r=' . $r : '?r=' . $r;

        return $url;
    }

    /**
     * @param $identifier
     * @param $byUrl
     * @param $createEntity
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