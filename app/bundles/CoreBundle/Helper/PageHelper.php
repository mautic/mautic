<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PageHelper implements PageHelperInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var string
     */
    private $sessionPrefix;

    /**
     * @var int
     */
    private $page;

    public function __construct(
        SessionInterface $session,
        CoreParametersHelper $coreParametersHelper,
        string $sessionPrefix,
        int $page
    ) {
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->sessionPrefix        = $sessionPrefix;
        $this->page                 = $page;
    }

    public function getLimit(): int
    {
        return (int) $this->session->get(
            "{$this->sessionPrefix}.limit",
            $this->coreParametersHelper->get('default_pagelimit')
        );
    }

    public function countPage(int $count): int
    {
        $currentPage = (int) (ceil($count / $this->getLimit())) ?: 1;

        return (1 === $count) ? 1 : $currentPage;
    }

    public function getStart(): int
    {
        $start = ($this->page - 1) * $this->getLimit();

        if ($start < 0) {
            return 0;
        }

        return $start;
    }

    public function rememberPage(int $page): void
    {
        $this->session->set("{$this->sessionPrefix}.page", $page);
    }
}
