<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Fixtures\Model;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\ImportModel;

final class ImportModelMock extends ImportModel
{
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }
}
