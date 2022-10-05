<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Templating\TemplateNameParser;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Kernel;

class TemplatingHelper
{
    /**
     * @var Container
     */
    protected $container;
    private \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine $delegatingEngine;

    public function __construct(Kernel $kernel, \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine $delegatingEngine)
    {
        $this->container = $kernel->getContainer();
        $this->delegatingEngine = $delegatingEngine;
    }

    /**
     * Retrieve the templating service.
     *
     * @return DelegatingEngine
     *
     *  @throws \Exception
     */
    public function getTemplating()
    {
        return $this->delegatingEngine;
    }

    /**
     * @return TemplateNameParser
     */
    public function getTemplateNameParser()
    {
        return new TemplateNameParser($this->container->get('kernel'));
    }
}
