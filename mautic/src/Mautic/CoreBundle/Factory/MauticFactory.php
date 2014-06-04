<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Factory;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class MauticFactory
{

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getModel($name)
    {
        static $models = array();

        if (!in_array($name, $models)) {
            if ($this->container->hasParameter('mautic.model.'.$name)) {
                $modelClass    = $this->container->getParameter(('mautic.model.'.$name));
                $models[$name] = new $modelClass(
                    $this->container->get('doctrine.orm.entity_manager'),
                    $this->container->get('mautic.security'),
                    $this->container->get('event_dispatcher'),
                    $this->container->get('translator'),
                    $this
                );
            } else {
                throw new NotAcceptableHttpException($name . " is not an acceptable model name.");
            }
        }

        return $models[$name];
    }
}