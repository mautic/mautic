<?php

namespace Mautic\CampaignBundle\Tests\Executioner\Result;

use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Mautic\LeadBundle\Entity\Lead;

class EvalutatedContactsTest extends \PHPUnit\Framework\TestCase
{
    public function testPassFail()
    {
        $evaluatedContacts = new EvaluatedContacts();
        $passLead          = new Lead();
        $evaluatedContacts->pass($passLead);

        $failedLead = new Lead();
        $evaluatedContacts->fail($failedLead);

        $passed = $evaluatedContacts->getPassed();
        $failed = $evaluatedContacts->getFailed();

        $this->assertCount(1, $passed);
        $this->assertCount(1, $failed);

        $this->assertTrue($passLead === $passed->first());
        $this->assertTrue($failedLead === $failed->first());
    }
}
