<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Templating\TemplateNameParser;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class TemplatingHelper.
 */
class TemplatingHelper
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * TemplatingHelper constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->container = $kernel->getContainer();
    }

    /**
     * Retrieve the templating service.
     *
     * @return DelegatingEngine
     *
     * @throws \Exception
     */
    public function getTemplating()
    {
        return $this->container->get('templating');
    }

    /**
     * @return TemplateNameParser
     *
     * @throws \Exception
     */
    public function getTemplateNameParser()
    {
        return new TemplateNameParser($this->container->get('kernel'));
    }
}
