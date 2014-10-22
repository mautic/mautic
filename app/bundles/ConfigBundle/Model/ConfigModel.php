<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Model;

use Mautic\CoreBundle\Model\CommonModel;

/**
 * Class ConfigModel
 */
class ConfigModel extends CommonModel
{

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'config:config';
    }

    /**
     * Creates the appropriate form per the model
     *
     * @param array                                        $data
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory
     * @param array                                        $options
     *
     * @return \Symfony\Component\Form\Form
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($data, $formFactory, $options = array())
    {
        return $formFactory->create('config', $data, $options);
    }
}
