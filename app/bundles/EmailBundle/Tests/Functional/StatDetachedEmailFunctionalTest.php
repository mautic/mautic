<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\EmailStatModel;

final class StatDetachedEmailFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testSavingStatWithDetachedEmailSucceeds(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');

        $email = $this->createEmail('Detached Email Test OK');
        $email->setEmailType('list');
        $email->setIsPublished(true);
        $em->persist($email);
        $em->flush();

        /** @var MailHelper $mailHelper */
        $mailHelper = static::getContainer()->get(MailHelper::class);
        /** @var EmailStatModel $emailStatModel */
        $emailStatModel = static::getContainer()->get(EmailStatModel::class);

        // Use the real Email entity in the helper
        $mailHelper->setEmail($email);

        // Force a detached state like it can happen in broadcast loops
        $em->detach($email);

        // With the fix in place, this should not throw
        $stat = $mailHelper->createEmailStat(false, 'recipient@example.test', null);
        $emailStatModel->saveEntity($stat);

        // If we reached here, the stat was saved without exception
        $this->addToAssertionCount(1);
    }
}
