<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class MauticFactory
{

    private $container;

    public function __construct(ContainerInterface $container)
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

        if (!array_key_exists($name, $models)) {
            $parts = explode('.', $name);
            if (count($parts) == 2) {
                $modelClass = '\\Mautic\\' . ucfirst($parts[0]) . 'Bundle\\Model\\' . ucfirst($parts[1]) . 'Model';
                if (class_exists($modelClass)) {
                    $models[$name] = new $modelClass($this);
                } else {
                    throw new NotAcceptableHttpException($name . " is not an acceptable model name.");
                }
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
     * @param $allowNull
     * @return mixed
     */
    public function getUser($allowNull = false)
    {
        $token = $this->getSecurityContext()->getToken();
        if (null !== $token) {
            return $token->getUser();
        } elseif ($allowNull) {
            return null;
        } else {
            return new User();
        }
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
        return $this->container->get('doctrine')->getManager();
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
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if (empty($request)) {
            //likely in a test as the request is not populated for outside the container
            $request = Request::createFromGlobals();
            $requestStack = new RequestStack();
            $requestStack->push($request);
            $this->requestStack = $requestStack;
        }
        return $request;
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
        return $this->container->getParameter('mautic.parameters');
    }

    /**
     * Retrieves a Mautic parameter
     *
     * @param $id
     * @return bool|mixed
     */
    public function getParameter($id)
    {
        return ($this->container->hasParameter('mautic.' . $id)) ?
            $this->container->getParameter('mautic.' . $id) :
            false;
    }

    /**
     * Get DateTimeHelper
     *
     * @param null $string
     * @param null $format
     * @param null $tz
     */
    public function getDate($string = null, $format = null, $tz = 'local')
    {
        static $dates;

        if (!empty($string)) {
            $key = "$string.$format.$tz";

            if (empty($dates[$key])) {
                $dates[$key] = new DateTimeHelper($string, $format, $tz);
            }
            return $dates[$key];
        } else {
            //now so generate a new helper
            return new DateTimeHelper($string, $format, $tz);
        }
    }

    /**
     * Get Router
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->container->get('router');
    }

    /**
     * Get the full path to specified area
     *
     * @param string $name
     * @param bool   $fullPath
     */
    public function getSystemPath($name, $fullPath = false)
    {
        $paths = $this->getParameter('paths');

        if ($name == 'currentTheme') {
            $theme = $this->getParameter('theme');
            $path  = $paths['theme'] . "/$theme";
        } elseif (isset($paths[$name])) {
            $path  = $paths[$name];
        } else {
            throw new \InvalidArgumentException("$name does not exist.");
        }

        return ($fullPath) ? $paths['root'] . '/' . $path : $path;
    }

    /**
     * Get the current environment
     *
     * @return mixed
     */
    public function getEnvironment()
    {
        return $this->container->getParameter('kernel.environment');
    }
}