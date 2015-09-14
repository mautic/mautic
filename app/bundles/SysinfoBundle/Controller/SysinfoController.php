<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SysinfoBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\SysinfoBundle\Event\ConfigEvent;
use Mautic\SysinfoBundle\Event\ConfigBuilderEvent;
use Mautic\SysinfoBundle\SysinfoEvents;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Symfony\Component\Form\FormError;

/**
 * Class SysinfoController
 */
class SysinfoController extends FormController
{

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction ($page = 1)
    {

        return $this->delegateView(array(
            'viewParameters'  => array(
            ),
            'contentTemplate' => 'MauticSysinfoBundle:Sysinfo:index.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_sysinfo_index',
                'mauticContent' => 'sysinfo',
                'route'         => $this->generateUrl('mautic_sysinfo_index')
            )
        ));
    }
}
