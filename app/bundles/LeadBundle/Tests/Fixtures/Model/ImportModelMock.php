<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Fixtures\Model;

use Mautic\LeadBundle\Model\ImportModel;
use Mautic\CoreBundle\Translation\Translator;

final class ImportModelMock extends ImportModel
{
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }
}
