<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PageHelper implements PageHelperInterface
{
    public function __construct(
        private SessionInterface $session,
        private CoreParametersHelper $coreParametersHelper,
        private string $sessionPrefix,
        private int $page
    ) {
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
