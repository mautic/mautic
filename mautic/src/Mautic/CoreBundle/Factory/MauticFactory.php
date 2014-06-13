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

    /**
     * @param $name
     * @return mixed
     */
    public function getModel($name)
    {
        static $models = array();

        if (!in_array($name, $models)) {
            if ($this->container->hasParameter('mautic.model.'.$name)) {
                $modelClass    = $this->container->getParameter(('mautic.model.'.$name));
                $models[$name] = new $modelClass($this);
            } else {
                throw new NotAcceptableHttpException($name . " is not an acceptable model name.");
            }
        }

        return $models[$name];
    }

    /**
     * Retrieves Mautic's security object
     *
     * @return object
     */
    public function getSecurity()
    {
        return $this->container->get('mautic.security');
    }

    /**
     * Retrieves Symfony's security context
     *
     * @return object
     */
    public function getSecurityContext()
    {
        return $this->container->get('security.context');
    }

    /**
     * Retrieves user currently logged in
     *
     * @return mixed
     */
    public function getUser()
    {
        return $this->getSecurity()->getCurrentUser();
    }

    /**
     * Retrieves session object
     *
     * @return object
     */
    public function getSession()
    {
        return $this->container->get('session');
    }

    /**
     * Retrieves Doctrine EntityManager
     *
     * @return object
     */
    public function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * Retrieves Translator
     *
     * @return object
     */
    public function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * Retrieves serializer
     *
     * @return object
     */
    public function getSerializer()
    {
        return $this->container->get('jms_serializer');
    }

    /**
     * Retrieves templating service
     *
     * @return object
     */
    public function getTemplating()
    {
        return $this->container->get('templating');
    }

    /**
     * Retrieves event dispatcher
     *
     * @return object
     */
    public function getDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * Retrieves request
     *
     * @return mixed
     */
    public function getRequest()
    {
        $stack = $this->container->get('request_stack');
        return $stack->getCurrentRequest();
    }

    /**
     * Retrieves Symfony's validator
     *
     * @return object
     */
    public function getValidator()
    {
        return $this->container->get('validator');
    }

    /**
     * Retrieves Mautic system parameters
     *
     * @return array
     */
    public function getSystemParameters()
    {
        return $this->getParam('mautic.parameters');
    }

    /**
     * Retrieves a parameter
     *
     * @param $id
     * @return bool|mixed
     */
    public function getParam($id)
    {
        return ($this->container->hasParameter($id)) ? $this->container->getParameter($id) : false;
    }
}