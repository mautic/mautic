<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class PageConfig implements PageConfigInterface
{
    public function __construct(private CoreParametersHelper $coreParametersHelper)
    {
    }

    public function isDraftEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('page_draft_enabled', false);
    }
}
