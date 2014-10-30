<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Integration;

/**
 * Class SugarCRMIntegration
 * @package Mautic\MapperBundle\Integration
 */
class SugarCRMIntegration extends AbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAppAlias()
    {
        return 'sugarcrm';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAppName()
    {
        return 'Sugar CRM';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getImage()
    {
        return 'app/bundles/MapperBundle/Assets/images/applications/sugarcrm_128.png';
    }
}