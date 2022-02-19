<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;

class EmailModelFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testSendEmailToTwoSameEmailAddressWithOptionSegmentEmailOnceToEmailAddress(): void
    {
        $contact1 = new Lead();
        $contact1->setEmail('test@test.com');
        $this->em->persist($contact1);

        $contact2 = clone $contact1;
        $this->em->persist($contact2);

        $segmentName    = 'Test_segment';
        $segment        = new LeadList();
        $segment->setName($segmentName);
        $segment->setPublicName($segmentName);
        $segment->setAlias($segmentName);
        $this->em->persist($segment);

        $contactSegment1 = new ListLead();
        $contactSegment1->setLead($contact1);
        $contactSegment1->setList($segment);
        $contactSegment1->setDateAdded(new \DateTime());
        $this->em->persist($contactSegment1);

        $contactSegment2 = new ListLead();
        $contactSegment2->setLead($contact2);
        $contactSegment2->setList($segment);
        $contactSegment2->setDateAdded(new \DateTime());
        $this->em->persist($contactSegment2);

        $this->em->flush();

        $emailName        = 'Test';
        $email            = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setCustomHTML('test content');
        $email->setEmailType('list');
        $email->setLists([$segment]);
        $email->setIsPublished(true);
        $this->em->persist($email);

        $email2 = clone $email;
        $email2->setIsPublished(true);
        $email2->setEmailType('list');
        $this->em->persist($email2);

        /** @var EmailModel $emailModel */
        $emailModel = self::$container->get('mautic.email.model.email');

        [$sentCount, $failedCount, $failedRecipientsByList] = $emailModel->sendEmailToLists($email);

        self::assertEquals(2, $sentCount, $email->getCustomHtml());

        $this->setUpSymfony(['segment_email_once_to_email_address' => true]);

        /** @var EmailModel $emailModel */
        $emailModel = self::$container->get('mautic.email.model.email');

        [$sentCount, $failedCount, $failedRecipientsByList] = $emailModel->sendEmailToLists($email2);

        self::assertEquals(1, $sentCount, $email2->getCustomHtml());
    }
}
