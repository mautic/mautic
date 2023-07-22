<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Fixtures\Model;

use Mautic\CoreBundle\Translation\Translator;

class ImportModel extends \Mautic\LeadBundle\Model\ImportModel
{
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }
}
