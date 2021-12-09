<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Helper\DTO;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Helper\DTO\SmsRecipientDTO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SmsRecipientDTOTest extends TestCase
{
    /**
     * @var MockObject|Lead
     */
    private $lead;

    /**
     * @var SmsRecipientDTO
     */
    private $dto1;

    /**
     * @var SmsRecipientDTO
     */
    private $dto2;

    /**
     * @var RecipientCollection<SmsRecipientDTO>
     */
    private $collection;

    public function testGetters(): void
    {
        $this->initData();

        $this->assertSame($this->lead, $this->dto1->getLead());
        $this->assertSame(1, $this->dto1->getKey());
        $this->assertJsonStringEqualsJsonString(
            '{"lead":{"imported":false,"deletedId":null},"result":false,"substitution_data":{}}',
            json_encode($this->dto1)
        );
        $this->assertJsonStringEqualsJsonString(
            '{"lead":{"imported":false,"deletedId":null},"result":false,"substitution_data":{"key":"value"}}',
            json_encode($this->dto2)
        );
        $this->assertSame(['key' => 'value'], $this->dto2->getSubstitutionData());

        $choices = $this->collection->toChoices();
        $this->assertSame(1, array_keys($choices)[0]);
        $recipient = $this->collection->getFieldByKey(2);
        $this->assertSame(2, $recipient->getKey());
    }

    private function initData(): void
    {
        $this->lead = $this->createMock(Lead::class);
        $this->lead->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $lead2 = $this->createMock(Lead::class);
        $lead2->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));

        $this->dto1 = new SmsRecipientDTO(
            $this->lead,
            []
        );

        $this->dto2 = new SmsRecipientDTO(
            $lead2,
            ['key' => 'value']
        );

        $this->collection = new RecipientCollection([$this->dto1, $this->dto2]);
    }
}
