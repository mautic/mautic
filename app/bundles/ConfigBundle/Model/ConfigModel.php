<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;

/**
 * Class ConfigModel.
 *
 * @deprecated 2.12.0; to be removed in 3.0 as this is pointless
 */
class ConfigModel extends AbstractCommonModel
{
    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'config:config';
    }

    /**
     * Creates the appropriate form per the model.
     *
     * @param array                                        $data
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory
     * @param array                                        $options
     *
     * @return \Symfony\Component\Form\Form
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($data, $formFactory, $options = [])
    {
        return $formFactory->create('config', $data, $options);
    }
}
