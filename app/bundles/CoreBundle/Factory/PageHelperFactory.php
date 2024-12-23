<?php

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PageHelper;
use Mautic\CoreBundle\Helper\PageHelperInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class PageHelperFactory implements PageHelperFactoryInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function make(string $sessionPrefix, int $page): PageHelperInterface
    {
        return new PageHelper($this->requestStack, $this->coreParametersHelper, $sessionPrefix, $page);
    }
}
