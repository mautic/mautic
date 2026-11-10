<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Form\Type;

use Mautic\LeadBundle\Field\FieldList;
use MauticPlugin\MauticEmailMarketingBundle\Client\ConstantContactClient;
use MauticPlugin\MauticEmailMarketingBundle\Client\IcontactClient;
use MauticPlugin\MauticEmailMarketingBundle\Client\MailchimpClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\ConstantContactType;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\FieldMappingType;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\IcontactType;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\MailchimpType;
use MauticPlugin\MauticEmailMarketingBundle\Helper\IntegrationKeys;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PushSettingsTypeTest extends TestCase
{
    private const array KEYS = ['password' => 'key-us7'];

    public function testMailchimpShowsTheListsAndDefaultsTheOptIns(): void
    {
        $client = $this->createStub(MailchimpClient::class);
        $client->method('getLists')->willReturn(['b2' => 'Zeta', 'a1' => 'Alpha']);

        $form = $this->createFactory(mailchimp: $client)->create(MailchimpType::class, []);

        $listSettings = $form->get('list_settings');
        $this->assertSame(['Alpha' => 'a1', 'Zeta' => 'b2'], $listSettings->get('list')->getConfig()->getOption('choices'));
        $this->assertTrue($listSettings->get('doubleOptin')->getData());
        $this->assertTrue($listSettings->get('sendWelcome')->getData());
        $this->assertFalse($listSettings->has('leadFields'), 'The mapping needs a list.');
    }

    public function testMailchimpMapsTheMergeFieldsOfTheSubmittedList(): void
    {
        $client = $this->createMock(MailchimpClient::class);
        $client->method('getLists')->willReturn(['a1' => 'Alpha']);
        $client->expects($this->once())
            ->method('getMergeFields')
            ->with(self::KEYS, 'a1')
            ->willReturn([
                'FNAME' => ['label' => 'First Name', 'required' => false],
                'EMAIL' => ['label' => 'Email', 'required' => true],
            ]);

        $form = $this->createFactory(mailchimp: $client)->create(MailchimpType::class);
        $form->submit(['list_settings' => ['list' => 'a1', 'doubleOptin' => '0', 'sendWelcome' => '1', 'leadFields' => ['EMAIL' => 'email', 'FNAME' => 'firstname']]]);

        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
        $this->assertEquals(
            ['list' => 'a1', 'doubleOptin' => 0, 'sendWelcome' => 1, 'leadFields' => ['EMAIL' => 'email', 'FNAME' => 'firstname']],
            $form->getData()['list_settings']
        );
    }

    public function testMailchimpRequiresTheEmailMapping(): void
    {
        $client = $this->createStub(MailchimpClient::class);
        $client->method('getLists')->willReturn(['a1' => 'Alpha']);
        $client->method('getMergeFields')->willReturn(['EMAIL' => ['label' => 'Email', 'required' => true]]);

        $form = $this->createFactory(mailchimp: $client)->create(MailchimpType::class);
        $form->submit(['list_settings' => ['list' => 'a1', 'leadFields' => ['EMAIL' => '']]]);

        $this->assertFalse($form->get('list_settings')->get('leadFields')->get('EMAIL')->isValid());
    }

    public function testMailchimpShowsApiErrorsOnTheList(): void
    {
        $client = $this->createStub(MailchimpClient::class);
        $client->method('getLists')->willThrowException(new ApiException('API Key Invalid'));
        $client->method('getMergeFields')->willThrowException(new ApiException('Resource Not Found'));

        $form = $this->createFactory(mailchimp: $client)->create(MailchimpType::class, ['list_settings' => ['list' => 'a1']]);

        $this->assertSame(['API Key Invalid', 'Resource Not Found'], $this->errors($form->get('list_settings')->get('list')));
        $this->assertFalse($form->get('list_settings')->has('leadFields'));
    }

    public function testConstantContactShowsItsListsAndFields(): void
    {
        $client = $this->createStub(ConstantContactClient::class);
        $client->method('getLists')->willReturn(['1' => 'Newsletter']);

        $form = $this->createFactory(constantContact: $client)->create(ConstantContactType::class, [
            'list_settings' => ['list' => '1', 'leadFields' => ['email' => 'email']],
        ]);

        $listSettings = $form->get('list_settings');
        $this->assertSame(['Newsletter' => 1], $listSettings->get('list')->getConfig()->getOption('choices'));
        $this->assertTrue($listSettings->get('sendWelcome')->getData());
        $this->assertSame('email', $listSettings->get('leadFields')->get('email')->getData());
        $this->assertTrue($listSettings->get('leadFields')->get('email')->isRequired());
        $this->assertSame('mautic.constantcontact.field.address_city', $listSettings->get('leadFields')->get('address_city')->getConfig()->getOption('label'));
        $this->assertSame('mautic.constantcontact.customfield15', $listSettings->get('leadFields')->get('customfield_15')->getConfig()->getOption('label'));
    }

    public function testConstantContactShowsApiErrorsOnTheList(): void
    {
        $client = $this->createStub(ConstantContactClient::class);
        $client->method('getLists')->willThrowException(new ApiException('Unauthorized'));

        $form = $this->createFactory(constantContact: $client)->create(ConstantContactType::class);

        $this->assertSame(['Unauthorized'], $this->errors($form->get('list_settings')->get('list')));
    }

    public function testIcontactShowsItsListsAndCustomFields(): void
    {
        $client = $this->createStub(IcontactClient::class);
        $client->method('getLists')->willReturn(['7' => 'Newsletter']);
        $client->method('getCustomFields')->willReturn(['color' => 'Favorite color']);

        $form = $this->createFactory(icontact: $client)->create(IcontactType::class);

        $listSettings = $form->get('list_settings');
        $this->assertSame(['Newsletter' => 7], $listSettings->get('list')->getConfig()->getOption('choices'));
        $this->assertSame('Favorite color', $listSettings->get('leadFields')->get('cf_color')->getConfig()->getOption('label'));
        $this->assertSame('mautic.icontact.field.postalCode', $listSettings->get('leadFields')->get('postalCode')->getConfig()->getOption('label'));
    }

    public function testIcontactShowsApiErrorsOnTheListOnce(): void
    {
        $client = $this->createStub(IcontactClient::class);
        $client->method('getLists')->willThrowException(new ApiException('Invalid AppId'));
        $client->method('getCustomFields')->willThrowException(new ApiException('Invalid AppId'));

        $form = $this->createFactory(icontact: $client)->create(IcontactType::class);

        $this->assertSame(['Invalid AppId'], $this->errors($form->get('list_settings')->get('list')));
    }

    /**
     * @return string[]
     */
    private function errors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }

    private function createFactory(
        ?MailchimpClient $mailchimp = null,
        ?ConstantContactClient $constantContact = null,
        ?IcontactClient $icontact = null,
    ): FormFactoryInterface {
        $integrationKeys = $this->createStub(IntegrationKeys::class);
        $integrationKeys->method('get')->willReturn(self::KEYS);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $fieldList = $this->createStub(FieldList::class);
        $fieldList->method('getFieldList')->willReturn(['Core' => ['email' => 'Email', 'firstname' => 'First Name']]);

        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addExtension(new PreloadedExtension([
                new MailchimpType($integrationKeys, $mailchimp ?? $this->createStub(MailchimpClient::class)),
                new ConstantContactType($integrationKeys, $constantContact ?? $this->createStub(ConstantContactClient::class), $translator),
                new IcontactType($integrationKeys, $icontact ?? $this->createStub(IcontactClient::class), $translator),
                new FieldMappingType($fieldList),
            ], []))
            ->getFormFactory();
    }
}
