<?php

namespace Mautic\CoreBundle\Templating\Engine;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine as BasePhpEngine;
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
     * @var Storage|null
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

    private $jsLoadMethodPrefix;

    /**
     * @param Stopwatch|GlobalVariables $delegateStopWatch
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

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function setRequestStack(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param string|\Symfony\Component\Templating\TemplateReferenceInterface $name
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
            ];
            if ('dev' === MAUTIC_ENV) {
                $dataArray['trace'] = $exception->getTrace();
            }

            return json_encode($dataArray);
        }

        return ErrorHandler::getHandler()->handleException($exception, true, true);
    }
}
