<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Factory;

use Doctrine\Bundle\DoctrineBundle\Registry;
use JMS\Serializer\Serializer;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator;

class MauticFactory
{

    private $dispatcher;
    private $db;
    private $requestStack;
    private $securityContext;
    private $security;
    private $serializer;
    private $session;
    private $templating;
    private $translator;
    private $validator;
    private $params;
    private $router;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        Registry $db,
        RequestStack $requestStack,
        SecurityContext $securityContext,
        CorePermissions $mauticSecurity,
        Serializer $serializer,
        Session $session,
        DelegatingEngine $templating,
        TranslatorInterface $translator,
        Validator $validator,
        Router $router,
        array $mauticParams
    ) {
        $this->dispatcher       = $dispatcher;
        $this->db               = $db;
        $this->requestStack     = $requestStack;
        $this->securityContext  = $securityContext;
        $this->security         = $mauticSecurity;
        $this->serializer       = $serializer;
        $this->session          = $session;
        $this->templating       = $templating;
        $this->translator       = $translator;
        $this->validator        = $validator;
        $this->router           = $router;
        $this->params           = $mauticParams;
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
        return $this->security;
    }

    /**
     * Retrieves Symfony's security context
     *
     * @return object
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
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
        return $this->session;
    }

    /**
     * Retrieves Doctrine EntityManager
     *
     * @return object
     */
    public function getEntityManager()
    {
        return $this->db->getManager();
    }

    /**
     * Retrieves Translator
     *
     * @return object
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Retrieves serializer
     *
     * @return object
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Retrieves templating service
     *
     * @return object
     */
    public function getTemplating()
    {
        return $this->templating;
    }

    /**
     * Retrieves event dispatcher
     *
     * @return object
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Retrieves request
     *
     * @return mixed
     */
    public function getRequest()
    {
        $request = $this->requestStack->getCurrentRequest();
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
        return $this->validator;
    }

    /**
     * Retrieves Mautic system parameters
     *
     * @return array
     */
    public function getSystemParameters()
    {
        return $this->params;
    }

    /**
     * Retrieves a Mautic parameter
     *
     * @param $id
     * @return bool|mixed
     */
    public function getParam($id)
    {
        return (isset($this->params[$id])) ? $this->params[$id] : false;
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
        return $this->router;
    }
}