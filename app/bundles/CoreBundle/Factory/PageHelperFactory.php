<?php

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PageHelper;
use Mautic\CoreBundle\Helper\PageHelperInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PageHelperFactory implements PageHelperFactoryInterface
{
    public function __construct(
        private SessionInterface $session,
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    public function make(string $sessionPrefix, int $page): PageHelperInterface
    {
        return new PageHelper($this->session, $this->coreParametersHelper, $sessionPrefix, $page);
    }
}
