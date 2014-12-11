<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Templating\DelegatingEngine;

/**
 * Class CommonController
 */
class CommonController extends Controller implements MauticController
{
    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param MauticFactory $factory
     *
     * @return void
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
    }

    /**
     * Determines if ajax content should be returned or direct content (page refresh)
     *
     * @param array $args
     *
     * @return JsonResponse|Response
     */
    function delegateView($args)
    {
        if (!is_array($args)) {
            $args = array(
                'contentTemplate' => $args,
                'passthroughVars' => array(
                    'mauticContent'   => strtolower($this->request->get('bundle'))
                )
            );
        }

        if (isset($args['passthroughVars']['route'])) {
            $args['viewParameters']['currentRoute'] = $args['passthroughVars']['route'];
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction($args);
        }

        $parameters = (isset($args['viewParameters'])) ? $args['viewParameters'] : array();
        $template   = $args['contentTemplate'];

        return $this->render($template, $parameters);
    }

    /**
     * Redirects URLs with trailing slashes in order to prevent 404s
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo   = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        return $this->redirect($url, 301);
    }

    /**
     * Redirects controller if not ajax or retrieves html output for ajax request
     *
     * @param array $args [returnUrl, viewParameters, contentTemplate, passthroughVars, flashes, forwardController]
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function postActionRedirect($args = array())
    {
        $returnUrl = array_key_exists('returnUrl', $args) ? $args['returnUrl'] : $this->generateUrl('mautic_dashboard_index');
        $flashes   = array_key_exists('flashes', $args) ? $args['flashes'] : array();

        //forward the controller by default
        $args['forwardController'] = (array_key_exists('forwardController', $args)) ? $args['forwardController'] : true;

        //set flashes
        if (!empty($flashes)) {
            foreach ($flashes as $flash) {
                $this->factory->getSession()->getFlashBag()->add(
                    $flash['type'],
                    $this->get('translator')->trans(
                        $flash['msg'],
                        (!empty($flash['msgVars']) ? $flash['msgVars'] : array()),
                        'flashes'
                    )
                );
            }
        }

        if (!$this->request->isXmlHttpRequest() || !empty($args['ignoreAjax'])) {
            $code = (isset($args['responseCode'])) ? $args['responseCode'] : 302;
            return $this->redirect($returnUrl, $code);
        }

        //load by ajax
        return $this->ajaxAction($args);
    }

    /**
     * Generates html for ajax request
     *
     * @param array $args [parameters, contentTemplate, passthroughVars, forwardController]
     *
     * @return JsonResponse
     */
    public function ajaxAction($args = array())
    {
        $parameters      = array_key_exists('viewParameters', $args) ? $args['viewParameters'] : array();
        $contentTemplate = array_key_exists('contentTemplate', $args) ? $args['contentTemplate'] : '';
        $passthrough     = array_key_exists('passthroughVars', $args) ? $args['passthroughVars'] : array();
        $forward         = array_key_exists('forwardController', $args) ? $args['forwardController'] : false;

        //set the route to the returnUrl
        if (empty($passthrough["route"]) && !empty($args["returnUrl"])) {
            $passthrough["route"] = $args["returnUrl"];
        }

        //Ajax call so respond with json
        if ($forward) {
            //the content is from another controller action so we must retrieve the response from it instead of
            //directly parsing the template
            $query              = array("ignoreAjax" => true, 'request' => $this->request, 'subrequest' => true);
            $newContentResponse = $this->forward($contentTemplate, $parameters, $query);
            $newContent         = $newContentResponse->getContent();
        } else {
            $newContent = $this->renderView($contentTemplate, $parameters);
        }

        //there was a redirect within the controller leading to a double call of this function so just return the content
        //to prevent newContent from being json
        if ($this->request->get('ignoreAjax', false)) {
            $response = new Response();
            $response->setContent($newContent);
            return $response;
        }

        $tmpl = (isset($parameters['tmpl'])) ? $parameters['tmpl'] : $this->request->get('tmpl', 'index');
        if ($tmpl == 'index') {
            if (!empty($passthrough["route"])) {
                //breadcrumbs may fail as it will retrieve the crumb path for currently loaded URI so we must override
                $this->request->query->set("overrideRouteUri", $passthrough["route"]);

                //if the URL has a query built into it, breadcrumbs may fail matching
                //so let's try to find it by the route name which will be the extras["routeName"] of the menu item
                $baseUrl = $this->request->getBaseUrl();
                $routePath = str_replace($baseUrl, '', $passthrough["route"]);
                try {
                    $routeParams = $this->get('router')->match($routePath);
                    $routeName   = $routeParams["_route"];
                    if (isset($routeParams["objectAction"])) {
                        //action urls share same route name so tack on the action to differentiate
                        $routeName .= "|{$routeParams["objectAction"]}";
                    }
                    $this->request->query->set("overrideRouteName", $routeName);
                } catch (\Exception $e) {
                    //do nothing
                }
            }

            $updatedContent = array();
            if (!empty($newContent)) {
                $updatedContent['newContent'] = $newContent;
            }

            $dataArray = array_merge(
                $updatedContent,
                $passthrough
            );
        } else {
            //just retrieve the content
            $dataArray = array_merge(
                array('newContent'  => $newContent),
                $passthrough
            );
        }

        $code = (isset($args['responseCode'])) ? $args['responseCode'] : 200;

        if ($newContent instanceof Response) {
            $response = $newContent;
        } else {
            $response = new JsonResponse($dataArray, $code);
        }

        //$response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }

    /**
     * Get's the content of error page
     *
     * @param \Exception $e
     *
     * @return Response
     */
    public function renderException(\Exception $e)
    {
        $exception   = FlattenException::create($e, $e->getCode(), $this->request->headers->all());
        $parameters  = array('request' => $this->request, 'exception' => $exception);
        $query       = array("ignoreAjax" => true, 'request' => $this->request, 'subrequest' => true);

        return $this->forward('MauticCoreBundle:Exception:show', $parameters, $query);
    }

    /**
     * Executes an action defined in route
     *
     * @param string $objectAction
     * @param int    $objectId
     * @param int    $objectSubId
     * @param string $objectModel
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeAction($objectAction, $objectId = 0, $objectSubId = 0, $objectModel = '')
    {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($objectId, $objectModel);
        }

        return $this->accessDenied();
    }

    /**
     * Generates access denied message
     *
     * @param bool   $batch Flag if a batch action is being performed
     * @param string $msg   Message to display to the user
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|array
     * @throws AccessDeniedHttpException
     */
    public function accessDenied($batch = false, $msg = 'mautic.core.error.accessdenied')
    {
        $anonymous = $this->factory->getSecurity()->isAnonymous();

        if ($anonymous || !$batch) {
            throw new AccessDeniedHttpException($this->get('translator')->trans($msg, array(), 'flashes'));
        }

        if ($batch) {
            return array(
                'type' => 'error',
                'msg'  => $msg
            );
        }
    }

    /**
     * Returns a json encoded access denied error for modal windows
     *
     * @param string $msg
     *
     * @return JsonResponse
     */
    public function modalAccessDenied($msg = 'mautic.core.error.accessdenied')
    {
        return new JsonResponse(array(
            'error' => $this->factory->getTranslator()->trans($msg, array(), 'flashes')
        ));
    }

    /**
     * Clear the application cache and run the warmup routine for the current environment
     *
     * @return void
     */
    public function clearCache()
    {
        ini_set('memory_limit', '128M');

        //attempt to squash command output
        ob_start();

        $env  = $this->factory->getEnvironment();
        $args = array('console', 'cache:clear', '--env=' . $env);

        if ($env == 'prod') {
            $args[] = '--no-debug';
        }

        $input       = new ArgvInput($args);
        $application = new Application($this->get('kernel'));
        $application->setAutoExit(false);
        $application->run($input);

        if (ob_get_length() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Delete's the file Symfony caches settings in
     */
    public function clearCacheFile()
    {
        $env      = $this->factory->getEnvironment();
        $debug    = ($this->factory->getDebugMode()) ? 'Debug' : '';
        $cacheDir = $this->factory->getSystemPath('cache', true);

        $cacheFile = "$cacheDir/app".ucfirst($env)."{$debug}ProjectContainer.php";

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /**
     * Updates the table's ordering in the session
     *
     * @return void
     */
    protected function setTableOrder()
    {
        $name    = InputHelper::clean($this->request->query->get('name'));
        $orderBy = InputHelper::clean($this->request->query->get('orderby'));
        if (!empty($name) && !empty($orderBy)) {
            $dir = $this->get('session')->get("mautic.$name.orderbydir", 'ASC');
            $dir = ($dir == 'ASC') ? 'DESC' : 'ASC';
            $this->get('session')->set("mautic.$name.orderby", $orderBy);
            $this->get('session')->set("mautic.$name.orderbydir", $dir);
        }
    }

    /**
     * Sets a specific theme for the form
     *
     * @param Form   $form
     * @param string $template
     * @param string $theme
     *
     * @return \Symfony\Component\Form\FormView
     */
    protected function setFormTheme(Form $form, $template, $theme = null)
    {
        $formView = $form->createView();

        if (empty($theme)) {
            return $formView;
        }

        $templating = $this->factory->getTemplating();

        if ($templating instanceof DelegatingEngine) {
            $templating->getEngine($template)->get('form')->setTheme($formView, $theme);
        } else {
            $templating->get('form')->setTheme($formView, $theme);
        }

        return $formView;
    }
}
