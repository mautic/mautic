<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\MailHelper;
use Mautic\CoreBundle\Templating\Helper\ThemeHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
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
     * @throws NotAcceptableHttpException
     */
    public function getModel($name)
    {
        static $models = array();

        //shortcut for models with same name as bundle
        if (strpos($name, '.') === false) {
            $name = "$name.$name";
        }

        if (!array_key_exists($name, $models)) {
            $parts = explode('.', $name);
            if (count($parts) == 2) {
                $modelClass = '\\Mautic\\' . ucfirst($parts[0]) . 'Bundle\\Model\\' . ucfirst($parts[1]) . 'Model';
                if (class_exists($modelClass)) {
                    $models[$name] = new $modelClass($this);

                    if (method_exists($models[$name], 'initialize')) {
                        $models[$name]->initialize();
                    }
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
     * @return \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    public function getSecurity()
    {
        return $this->container->get('mautic.security');
    }

    /**
     * Retrieves Symfony's security context
     *
     * @return \Symfony\Component\Security\Core\SecurityContext
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
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public function getSession()
    {
        return $this->container->get('session');
    }

    /**
     * Retrieves Doctrine EntityManager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * Retrieves Doctrine db connection for DBAL use
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDatabase()
    {
        return $this->container->get('database_connection');
    }

    /**
     * Gets a schema helper for manipulating database schemas
     *
     * @param      $type
     * @param null $name Object name; i.e. table name
     * @return mixed
     */
    public function getSchemaHelper($type, $name = null)
    {
        static $schemaHelpers = array();

        if (empty($schemaHelpers[$type])) {
            $className            = "\\Mautic\\CoreBundle\\Doctrine\\Helper\\" . ucfirst($type).'SchemaHelper';
            if ($type == "table") {
                //get the column helper as well
                $columnHelper         = $this->getSchemaHelper('column');
                $schemaHelpers[$type] = new $className($this->getDatabase(), $this->getParameter('db_table_prefix'), $columnHelper);
            } else {
                $schemaHelpers[$type] = new $className($this->getDatabase(), $this->getParameter('db_table_prefix'));
            }

        }

        if ($name !== null) {
            $schemaHelpers[$type]->setName($name);
        }

        return $schemaHelpers[$type];
    }

    /**
     * Retrieves Translator
     *
     * @return \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * Retrieves serializer
     *
     * @return \JMS\Serializer\Serializer
     */
    public function getSerializer()
    {
        return $this->container->get('jms_serializer');
    }

    /**
     * Retrieves templating service
     *
     * @return \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    public function getTemplating()
    {
        return $this->container->get('templating');
    }

    /**
     * Retrieves event dispatcher
     *
     * @return \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    public function getDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * Retrieves request
     *
     * @return \Symfony\Component\HttpFoundation\Request|null
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
     * @return \Symfony\Component\Validator\Validator
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
     * @param string $string
     * @param string $format
     * @param string $tz
     * @return DateTimeHelper
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
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getSystemPath($name, $fullPath = false)
    {
        $paths = $this->getParameter('paths');

        if ($name == 'currentTheme') {
            $theme = $this->getParameter('theme');
            $path  = $paths['themes'] . "/$theme";
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

    /**
     * returns a ThemeHelper instance for the given theme
     *
     * @param string $theme
     * @return \Mautic\CoreBundle\Templating\Helper\ThemeHelper
     */
    public function getTheme($theme = 'current')
    {
        static $themeHelpers = array();

        if (empty($themeHelpers[$theme])) {
            $themeHelpers[$theme] = new ThemeHelper($this, $theme);
        }

        return $themeHelpers[$theme];
    }

    /**
     * Gets a list of installed themes
     *
     * @param string $specificFeature limits list to those that support a specific feature
     * @return array
     */
    public function getInstalledThemes($specificFeature = 'all')
    {
        static $themes = array();

        if (empty($themes[$specificFeature])) {
            $dir = $this->getSystemPath('themes', true);

            $finder = new Finder();
            $finder->directories()->depth('0')->ignoreDotFiles(true)->in($dir);

            $themes[$specificFeature] = array();
            foreach ($finder as $theme) {
                if (file_exists($theme->getRealPath() . '/config.php')) {
                    $config = include $theme->getRealPath() . '/config.php';
                    if ($specificFeature != 'all') {
                        if (isset($config['features']) && in_array($specificFeature, $config['features'])) {
                            $themes[$specificFeature][$theme->getBasename()] = $config['name'];
                        }
                    } else {
                        $themes[$specificFeature][$theme->getBasename()] = $config['name'];
                    }
                }
            }
        }

        return $themes[$specificFeature];
    }

    /**
     * Get AssetsHelper
     *
     * @return \Mautic\CoreBundle\Templating\Helper\AssetsHelper
     */
    public function getAssetsHelper()
    {
        return $this->container->get('templating.helper.assets');
    }

    /**
     * Returns MailHelper wrapper for Swift_Message via $helper->message
     *
     * @return MailHelper
     */
    public function getMailer()
    {
        return new MailHelper($this, $this->container->get('mailer'), array(
            $this->getParameter('mailer_from_email') => $this->getParameter('mailer_from_name')
        ));
    }

    /**
     * Get an IpAddress entity for current session
     *
     * @return IpAddress
     */
    public function getIpAddress()
    {
        static $ipAddress;

        if (empty($ipAddress)) {
            $request = $this->getRequest();
            $ip      = $request->server->get('REMOTE_ADDR');

            $ipAddress = $this->getEntityManager()->getRepository('MauticCoreBundle:IpAddress')
                ->findOneByIpAddress($ip);

            if ($ipAddress === null) {
                $ipAddress = new IpAddress();
                $ipAddress->setIpAddress($ip, $this->getSystemParameters());
            }
        }

        return $ipAddress;
    }
}
