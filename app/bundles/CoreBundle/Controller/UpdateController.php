<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

/**
 * Class UpdateController
 */
class UpdateController extends CommonController
{
    /**
     * Generates the update view
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        /** @var \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper */
        $updateHelper = $this->factory->getHelper('update');
        $updateData   = $updateHelper->fetchData();

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'updateData'     => $updateData,
                'currentVersion' => $this->factory->getVersion()
            ),
            'contentTemplate' => 'MauticCoreBundle:Update:index.html.php',
            'passthroughVars' => array(
                'mauticContent'  => 'update',
                'route'          => $this->generateUrl('mautic_core_update')
            )
        ));
    }
}
