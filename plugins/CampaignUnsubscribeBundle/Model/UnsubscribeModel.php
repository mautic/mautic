<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CampaignUnsubscribeBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;

/**
 * Class UnsubscribeModel
 * {@inheritdoc}
 */
class UnsubscribeModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $params = [])
    {
        if (!$entity instanceof Monitoring) {
            throw new MethodNotAllowedHttpException(['Monitoring']);
        }

        if (!empty($action)) {
            $params['action'] = $action;
        }

        return $formFactory->create('monitoring', $entity, $params);
    }
}