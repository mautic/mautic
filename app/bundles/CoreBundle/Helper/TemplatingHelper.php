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

    public function __construct(Kernel $kernel)
    {
        $this->container = $kernel->getContainer();
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
        return $this->container->get('templating');
    }

    /**
     * @return TemplateNameParser
     */
    public function getTemplateNameParser()
    {
        return new TemplateNameParser($this->container->get('kernel'));
    }
}
