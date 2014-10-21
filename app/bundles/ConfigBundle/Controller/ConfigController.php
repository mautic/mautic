<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;

/**
 * Class ConfigController
 */
class ConfigController extends FormController
{

    /**
     * Controller action for viewing the application configuration
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array('config:config:full'), "RETURN_ARRAY");

        if (!$permissions['config:config:full']) {
            return $this->accessDenied();
        }

        $params = $this->getBundleParams();

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'params'      => $params,
                'permissions' => $permissions,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticConfigBundle:Config:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_config_index',
                'mauticContent'  => 'config',
                'route'          => $this->generateUrl('mautic_config_index'),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    /**
     * Retrieves the parameters defined in each bundle and merges with the local params
     *
     * @return array
     */
    private function getBundleParams()
    {
        require $this->container->getParameter('kernel.root_dir') . '/config/local.php';
        $localParams = $parameters;

        $params = array();
        $mauticBundles = $this->factory->getParameter('bundles');

        foreach ($mauticBundles as $bundle) {
            // Build the path to the bundle configuration
            $paramsFile = $bundle['directory'] . '/Config/parameters.php';

            if (file_exists($paramsFile)) {
                require_once $paramsFile;
                foreach ($parameters as $key => $value) {
                    if (array_key_exists($key, $localParams)) {
                        $parameters[$key] = $localParams[$key];
                    }
                }
                $params[$bundle['bundle']] = $parameters;
            }
        }

        return $params;
    }
}
