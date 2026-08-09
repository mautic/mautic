<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Form\Type;

use Mautic\CoreBundle\Factory\IpLookupFactory;
use Mautic\CoreBundle\Form\Type\ConfigType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Form\Type\PageListType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class ConfigTypeTest extends TypeTestCase
{
    public function testSubmitEmptyTrustedHosts(): void
    {
        $formData = [
            'site_url'             => 'http://example.com',
            'cache_path'           => 'tmp',
            'log_path'             => '/var/log',
            'image_path'           => 'media/images/',
            'cached_data_timeout'  => 30000,
            'date_format_full'     => 'F j, Y g:i:s a T',
            'date_format_short'    => 'D, M d - g:i:s a',
            'date_format_dateonly' => 'F j, Y',
            'date_format_timeonly' => 'g:i:s a',
            'trusted_hosts'        => '',
        ];

        // $formData will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(ConfigType::class, $formData);

        // submit the data to the form directly
        $form->submit($formData);

        // This check ensures there are no transformation failures
        $this->assertTrue($form->isSynchronized());

        // check that $formData was modified as expected when the form was submitted
        $this->assertTrue($form->isValid());
    }

    #[DataProvider('provideInvalidHostAndRegexp')]
    public function testSubmitInvalidTrustedHost(string $invalidHost): void
    {
        $formData = [
            'site_url'             => 'http://example.com',
            'cache_path'           => 'tmp',
            'log_path'             => '/var/log',
            'image_path'           => 'media/images/',
            'cached_data_timeout'  => 30000,
            'date_format_full'     => 'F j, Y g:i:s a T',
            'date_format_short'    => 'D, M d - g:i:s a',
            'date_format_dateonly' => 'F j, Y',
            'date_format_timeonly' => 'g:i:s a',
            'trusted_hosts'        => $invalidHost,
        ];

        // $formData will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(ConfigType::class, $formData);

        // submit the data to the form directly
        $form->submit($formData);

        // This check ensures there are no transformation failures
        $this->assertTrue($form->isSynchronized());

        // check that $formData was modified as expected when the form was submitted
        $this->assertFalse($form->isValid());
    }

    public static function provideInvalidHostAndRegexp(): \Generator
    {
        yield 'trusted..com' => ['trusted..com']; // Invalid host.
        yield 'trusted.com' => ['trusted.com/']; // Host with trailing slash
        yield 'trusted.com\\' => ['trusted.com\\']; // Host with trailing backslash
        yield '[trusted.com' => ['[trusted.com']; // Invalid regexp #1
        yield 'trusted(.com' => ['trusted(.com']; // Invalid regexp #2
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'site_url'             => 'http://example.com',
            'cache_path'           => 'tmp',
            'log_path'             => '/var/log',
            'image_path'           => 'media/images/',
            'cached_data_timeout'  => 30000,
            'date_format_full'     => 'F j, Y g:i:s a T',
            'date_format_short'    => 'D, M d - g:i:s a',
            'date_format_dateonly' => 'F j, Y',
            'date_format_timeonly' => 'g:i:s a',
            'trusted_hosts'        => '.*\.?trusted.com$,trusted.com,example.com, example.?om,sub1.sub2.sub3.example.com',
        ];

        // $formData will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(ConfigType::class, $formData);

        // submit the data to the form directly
        $form->submit($formData);

        // This check ensures there are no transformation failures
        $this->assertTrue($form->isSynchronized());

        // check that $formData was modified as expected when the form was submitted
        $this->assertTrue($form->isValid());
    }

    private function getConfigFormType(): ConfigType
    {
        $languageHelper             = $this->createMock(LanguageHelper::class);

        $languageHelper
                       ->method('fetchLanguages')
                       ->willReturn(['en' => ['name'=>'English']]);

        return new ConfigType($this->createStub(TranslatorInterface::class), $languageHelper, $this->createStub(IpLookupFactory::class), null, $this->createStub(Shortener::class), $this->createStub(CoreParametersHelper::class));
    }

    /**
     * @return array<int, PreloadedExtension|ValidatorExtension>
     */
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        // or if you also need to read constraints from annotations
        $validator = Validation::createValidatorBuilder()
            ->getValidator();
        // create a type instance with the mocked dependencies
        $configType = $this->getConfigFormType();

        $repoMock = $this->createMock(PageRepository::class);
        $repoMock
                 ->method('getPageList')
                 ->willReturn([]);

        $pageListType = new PageListType($this->createStub(CorePermissions::class), $repoMock);

        return [
            // register the type instances with the PreloadedExtension
            new ValidatorExtension($validator),
            new PreloadedExtension([$configType, $pageListType], []),
        ];
    }
}
