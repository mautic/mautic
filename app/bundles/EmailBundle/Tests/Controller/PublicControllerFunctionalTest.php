<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;

class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testUnsubscribeAction(): void
    {
        $form = new Form();
        $formName = 'Unsubscribe_test_form';
        $form->setName($formName);
        $form->setAlias($formName);
        $this->em->persist($form);

        $email = new Email();
        $email->setName("Email");
        $email->setSubject("EmailSubject");
        $email->setEmailType('template');
        $email->setUnsubscribeForm($form);
        $this->em->persist($email);


        // Create a test email stat.
        $stat = new Stat();
        $trackingHash = 'test_unsubscribe_form_email';
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress('john@doe.email');
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($email);
        $this->em->persist($stat);

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$trackingHash);

        self::assertStringContainsString('form/submit?formId='.$form->getId(), $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

}
