<?php

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PageHelper;
use Mautic\CoreBundle\Helper\PageHelperInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PageHelperFactory implements PageHelperFactoryInterface
{
    private \Symfony\Component\HttpFoundation\Session\SessionInterface $session;

    private \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper;

    public function __construct(SessionInterface $session, CoreParametersHelper $coreParametersHelper)
    {
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function make(string $sessionPrefix, int $page): PageHelperInterface
    {
        return new PageHelper($this->session, $this->coreParametersHelper, $sessionPrefix, $page);
    }
}
