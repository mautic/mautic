<?php
/**
* @package     Mautic
* @copyright   2014 Mautic, NP. All rights reserved.
* @author      Mautic
* @link        http://mautic.com
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\MapperBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;

class MapperController extends FormController
{
    /**
     * @param        $bundle
     * @param        $objectAction
     * @param int    $objectId
     * @param string $objectModel
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeMapperAction($application, $client, $object, $objectAction = '') {
        if (method_exists($this, "{$objectAction}MapperAction")) {
            return $this->{"{$objectAction}MapperAction"}($application, $client, $object);
        } else {
            return $this->accessDenied();
        }
    }

    public function indexAction($application, $client)
    {
        if (!$this->factory->getSecurity()->isGranted($application.':mapper:create')) {
            return $this->accessDenied();
        }

        $entities = array();
        $bundles = $this->factory->getParameter('bundles');
        $bundle = $bundles[ucfirst($application)];

        $finder = new Finder();
        $finder->files()->name('*Mapper.php')->in($bundle['directory'] . '/Mapper');
        $finder->sortByName();
        foreach ($finder as $file) {
            $class = sprintf('\\Mautic\%s\Mapper\%s', $bundle['bundle'], substr($file->getBaseName(), 0, -4));
            $object = new $class;
            $entities[] = $object;
        }

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            $application.':mapper:view',
            $application.':mapper:create',
            $application.':mapper:edit',
            $application.':mapper:delete'
        ), "RETURN_ARRAY");

        $viewParams = array(
            'client'   => $client,
            'application' => $application
        );

        $tmpl = $this->request->get('tmpl', 'index');

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_mapper_client_objects_index', $viewParams),
            'viewParameters'  => array(
                'application' => $application,
                'client'      => $client,
                'items'       => $entities,
                'permissions' => $permissions,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticMapperBundle:Mapper:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_'.$application.'client_'.$client.'objects_index',
                'mauticContent'  => 'clients',
                'route'          => $this->generateUrl('mautic_mapper_client_objects_index', $viewParams)
            )
        ));
    }
}
