<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class EmailTokenTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testEmailTokens(): void
    {
        $lead = $this->createLeadWithAllFields();

        $email = new Email();
        $email->setEmailType('list');
        $email->setName('CO token test email');
        $email->setSubject('CO token test email');
        $email->setCustomHtml('
            
            Dear %7Bcontactfield=firstname%7D {contactfield=lastname},
            
            Check these fields:
            Mobile: %7Bcontactfield%3Dmobile%7D
            Address: {contactfield=address1}, {contactfield=address2}, {contactfield=city}, {contactfield=country}
            Email: {contactfield=email} 
            
            Custom Values:
            
            Contact:
            Bool: {contactfield=boollead},
            Date: {contactfield=datelead},
            Date/Time: {contactfield=datetimelead}
            Email: {contactfield=emaillead}
            HTML: {contactfield=htmllead}
            Country: {contactfield=countrylead}
            Locale: {contactfield=localelead}
            Number: {contactfield=numberlead}
            Phone: {contactfield=phonelead}
            Region: {contactfield=regionlead}
            Text: {contactfield=textlead}
            Textarea: {contactfield=textarealead}
            Time: {contactfield=timelead}
            Timezone: {contactfield=timezonelead}
            URL: {contactfield=urllead}
        ');

        $this->em->persist($email);
        $this->em->flush();

        /** @var EmailModel $emailModel */
        $emailModel = self::getContainer()->get('mautic.email.model.email');
        $emailModel->sendEmail(
            $email,
            [
                [
                    'id'           => $lead->getId(),
                    'email'        => $lead->getEmail(),
                    'firstname'    => $lead->getFirstname(),
                    'lastname'     => $lead->getLastname(),
                    'mobile'       => $lead->getMobile(),
                    'address1'     => $lead->getAddress1(),
                    'address2'     => $lead->getAddress2(),
                    'city'         => $lead->getCity(),
                    'country'      => $lead->getCountry(),
                    'boollead'     => $lead->getUpdatedFields()['boollead'],
                    'datelead'     => $lead->getUpdatedFields()['datelead'],
                    'datetimelead' => $lead->getUpdatedFields()['datetimelead'],
                    'emaillead'    => $lead->getUpdatedFields()['emaillead'],
                    'htmllead'     => $lead->getUpdatedFields()['htmllead'],
                    'countrylead'  => $lead->getUpdatedFields()['countrylead'],
                    'localelead'   => $lead->getUpdatedFields()['localelead'],
                    'numberlead'   => $lead->getUpdatedFields()['numberlead'],
                    'phonelead'    => $lead->getUpdatedFields()['phonelead'],
                    'regionlead'   => $lead->getUpdatedFields()['regionlead'],
                    'textlead'     => $lead->getUpdatedFields()['textlead'],
                    'textarealead' => $lead->getUpdatedFields()['textarealead'],
                    'timelead'     => $lead->getUpdatedFields()['timelead'],
                    'timezonelead' => $lead->getUpdatedFields()['timezonelead'],
                    'urllead'      => $lead->getUpdatedFields()['urllead'],
                ],
            ]
        );

        /** @var StatRepository $emailStatRepository */
        $emailStatRepository = $this->em->getRepository(Stat::class);

        /** @var Stat|null $emailStat */
        $emailStat = $emailStatRepository->findOneBy(
            [
                'email' => $email->getId(),
                'lead'  => $lead->getId(),
            ]
        );

        Assert::assertNotNull($emailStat);

        $crawler = $this->client->request(Request::METHOD_GET, "/email/view/{$emailStat->getTrackingHash()}");

        $body = $crawler->filter('body');

        // Remove the tracking tags that are causing troubles with different Mautic configurations.
        $body->filter('a,img,div')->each(function (Crawler $crawler) {
            foreach ($crawler as $node) {
                $node->parentNode->removeChild($node);
            }
        });

        Assert::assertSame(
            $this->stripWhiteSpaces('Dear Test Lead,
            
            Check these fields:
            Mobile: 012
            Address: Lane 11, Near Post Office, Pune, India
            Email: test@domain.tld 
            
            Custom Values:
            
            Contact:
            Bool: 1,
            Date: 2022-07-01,
            Date/Time: 2022-07-01 20:22
            Email: test@test.com
            HTML: <p>This is some normal text.</p>
            Country: India
            Locale: Hindi
            Number: 400
            Phone: 1234567
            Region: Maharashtra
            Text: this is text
            Textarea: This is a paragraph
            Time: 20:00
            Timezone: Kolkata
            URL: www.example.com'),
            $this->stripWhiteSpaces($body->html())
        );
    }

    /**
     * @return array <mixed>
     */
    private function customFieldTypes(): array
    {
        return [
            'bool'     => ['boolean', true],
            'date'     => ['date', '2022-07-01'],
            'datetime' => ['datetime', '2022-07-01 20:22'],
            'email'    => ['email', 'test@test.com'],
            'html'     => ['html', '<p>This is some normal text.</p>'],
            'country'  => ['country', 'India'],
            'locale'   => ['locale', 'Hindi'],
            'number'   => ['number', 400],
            'phone'    => ['tel', 1234567],
            'region'   => ['region', 'Maharashtra'],
            'text'     => ['text', 'this is text'],
            'textarea' => ['textarea', 'This is a paragraph'],
            'time'     => ['time', '20:00'],
            'timezone' => ['timezone', 'Kolkata'],
            'url'      => ['url', 'www.example.com'],
        ];
    }

    private function createLeadWithAllFields(): Lead
    {
        $leadModel  = self::getContainer()->get('mautic.lead.model.lead');
        $fieldModel = self::getContainer()->get('mautic.lead.model.field');

        $lead = new Lead();
        $lead->setFirstname('Test');
        $lead->setLastname('Lead');
        $lead->setMobile('012');
        $lead->setAddress1('Lane 11');
        $lead->setAddress2('Near Post Office');
        $lead->setCity('Pune');
        $lead->setCountry('India');
        $lead->setEmail('test@domain.tld');
        $lead->setCompany('Acquia');

        foreach ($this->customFieldTypes() as $alias => [$type, $value]) {
            $customFieldLead = new LeadField();
            $customFieldLead->setLabel($alias.'lead');
            $customFieldLead->setAlias($alias.'lead');
            $customFieldLead->setType($type);
            $customFieldLead->setObject('lead');
            $customFieldLead->setIsPublished(true);
            $fieldModel->saveEntity($customFieldLead);

            $lead->addUpdatedField($customFieldLead->getAlias(), $value);
        }

        $leadModel->saveEntity($lead);
        $this->em->clear();

        return $lead;
    }

    private function stripWhiteSpaces(string $string): string
    {
        return preg_replace('/\s+/', '', $string);
    }
}
