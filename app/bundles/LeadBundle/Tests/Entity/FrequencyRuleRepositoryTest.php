<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;

class FrequencyRuleRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @var FrequencyRuleRepository
     */
    private $frequencyRuleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frequencyRuleRepository = self::$container->get('mautic.lead.repository.frequency_rule');
    }
}
