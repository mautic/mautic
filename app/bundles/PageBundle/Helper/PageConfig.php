<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class PageConfig implements PageConfigInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function isDraftEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('page_draft_enabled', false);
    }
}
