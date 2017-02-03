<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Engine;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine as BasePhpEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\Storage\FileStorage;
use Symfony\Component\Templating\Storage\Storage;
use Symfony\Component\Templating\TemplateNameParserInterface;

/**
 * PhpEngine is an engine able to render PHP templates.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class PhpEngine extends BasePhpEngine
{
    /**
     * @var
     */
    private $evalTemplate;

    /**
     * @var \Exception|null
     */
    private $exception;

    /**
     * @var GlobalVariables|Stopwatch
     */
    private $stopwatch;

    /**
     * @var bool
     */
    private $parsingException = false;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var
     */
    private $jsLoadMethodPrefix;

    /**
     * PhpEngine constructor.
     *
     * @param TemplateNameParserInterface $parser
     * @param ContainerInterface          $container
     * @param LoaderInterface             $loader
     * @param Stopwatch|GlobalVariables   $delegateStopWatch
     * @param GlobalVariables|null        $globals
     */
    public function __construct(
        TemplateNameParserInterface $parser,
        ContainerInterface $container,
        LoaderInterface $loader,
        $delegateStopWatch,
        GlobalVariables $globals = null
    ) {
        if ($delegateStopWatch instanceof Stopwatch) {
            $this->stopwatch = $delegateStopWatch;
        } else {
            $globals = $delegateStopWatch;
        }

        parent::__construct($parser, $container, $loader, $globals);
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param string|\Symfony\Component\Templating\TemplateReferenceInterface $name
     * @param array                                                           $parameters
     *
     * @return false|string
     */
    public function render($name, array $parameters = [])
    {
        // Set the javascript loader for subsequent templates
        if (isset($parameters['mauticContent'])) {
            $this->jsLoadMethodPrefix = $parameters['mauticContent'];
        } elseif (!empty($this->jsLoadMethodPrefix)) {
            $parameters['mauticContent'] = $this->jsLoadMethodPrefix;
        }

        defined('MAUTIC_RENDERING_TEMPLATE') || define('MAUTIC_RENDERING_TEMPLATE', 1);
        if ($this->dispatcher->hasListeners(CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE)) {
            $event = $this->dispatcher->dispatch(
                CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE,
                new CustomTemplateEvent($this->request, $name, $parameters)
            );

            $name       = $event->getTemplate();
            $parameters = $event->getVars();
        }

        $parameters['mauticTemplate'] = $name;

        if ($this->stopwatch) {
            $e = $this->stopwatch->start(sprintf('template.php (%s)', $name), 'template');
        }

        $content = parent::render($name, $parameters);

        if ($this->stopwatch) {
            $e->stop();
        }

        return $content;
    }

    /**
     * @param Storage $template
     * @param array   $mauticTemplateVars
     *
     * @return false|string
     *
     * @throws \Exception
     */
    protected function evaluate(Storage $template, array $mauticTemplateVars = [])
    {
        if (!$template instanceof FileStorage) {
            return parent::evaluate($template, $mauticTemplateVars);
        }

        $this->evalTemplate = $template;
        unset($template);
        unset($mauticTemplateVars['this']);
        $mauticTemplateVars['view'] = $this;

        extract($mauticTemplateVars, EXTR_SKIP);
        ob_start();
        try {
            require $this->evalTemplate;
        } catch (\Exception $e) {
            // Catch the exception and throw it outside of ob in case the exception occurred within an ajax request
            // corrupting the JSON response
            $this->exception = $e;
        }
        $return = ob_get_clean();

        if ($this->exception) {
            if (!$this->parsingException) {
                $return = $this->generateErrorContent($this->exception);
            }
            $this->exception = null;
        }

        $this->evalTemplate     = null;
        $this->parsingException = false;

        return $return;
    }

    /**
     * @param \Exception $exception
     *
     * @return false|string
     */
    protected function generateErrorContent(\Exception $exception)
    {
        defined('MAUTIC_TEMPLATE_EXCEPTION') || define('MAUTIC_TEMPLATE_EXCEPTION', 1);

        if (defined('MAUTIC_API_REQUEST') && MAUTIC_API_REQUEST) {
            $dataArray = [
                'errors' => [
                    [
                        'message' => $exception->getMessage(),
                        'code'    => 500,
                        'type'    => null,
                    ],
                ],
                // @deprecated 2.6.0 to be removed in 3.0
                'error' => [
                    'message' => $exception->getMessage().' (`error` is deprecated as of 2.6.0 and will be removed in 3.0. Use the `errors` array instead.)',
                    'code'    => 500,
                ],
            ];
            if ('dev' === MAUTIC_ENV) {
                $dataArray['trace'] = $exception->getTrace();
            }

            return json_encode($dataArray);
        }

        return ErrorHandler::getHandler()->handleException($exception, true, true);
    }
}
