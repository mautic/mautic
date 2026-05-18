<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Validator\Constraint;

use Mautic\FormBundle\Finder\Tokens\RedirectUrlTokensFinder;
use Mautic\FormBundle\Validator\Constraint\IsPostActionRedirectUrl;
use Mautic\FormBundle\Validator\Constraint\IsPostActionRedirectUrlValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(IsPostActionRedirectUrlValidator::class)]
class IsPostActionRedirectUrlValidatorTest extends ConstraintValidatorTestCase
{
    private ValidatorInterface|MockObject $urlValidator;

    public static function provideEmptyValue(): \Generator
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }

    public static function provideIncorrectUrl(): \Generator
    {
        yield 'not valid url 1' => [
            'example',
            null,
        ];

        yield 'not valid url 2' => [
            'ttps://example.com',
            null,
        ];

        yield 'not valid url 3' => [
            'https://example',
            null,
        ];

        yield 'not valid url 4' => [
            'https:/example.com',
            null,
        ];

        yield 'not valid url 5' => [
            'example.com?test1=123&test2=abc',
            null,
        ];

        // ---

        yield 'not valid url 1 - with tokens' => [
            'example?{formfield=abc}&{contactfield=abc123}',
            'example?formfield-1&contactfield-2',
        ];

        yield 'not valid url 2 - with tokens' => [
            'ttps://example.com?{formfield=abc}&{contactfield=abc123}',
            'ttps://example.com?formfield-1&contactfield-2',
        ];

        yield 'not valid url 3 - with tokens' => [
            'https://example?{formfield=abc}&{contactfield=abc123}',
            'https://example?formfield-1&contactfield-2',
        ];

        yield 'not valid url 4 - with tokens' => [
            'https:/example.com?{formfield=abc}&{contactfield=abc123}',
            'https:/example.com?formfield-1&contactfield-2',
        ];

        yield 'not valid url 5 - with tokens' => [
            'example.com?test1=123&test2=abc&{formfield=abc}&{contactfield=abc123}',
            'example.com?test1=123&test2=abc&formfield-1&contactfield-2',
        ];

        yield 'missing curly braces in some tokens' => [
            'https:/example.com?formfield=abc}&{contactfield=abc123',
            'https:/example.com?formfield=abc}&{contactfield=abc123',
        ];

        yield 'with unknown tokens 1' => [
            'https:/example.com?{formfield=abc}&{contactfield=abc123}&{foo=bar}&{lorem=ipsum}',
            'https:/example.com?formfield-1&contactfield-2&{foo=bar}&{lorem=ipsum}',
        ];

        yield 'with unknown tokens 2' => [
            '{pagelink=123}?{formfield=abc}&{contactfield=abc123}&{foo=bar}&{lorem=ipsum}',
            'https://example.com?formfield-2&contactfield-3&{foo=bar}&{lorem=ipsum}',
        ];
    }

    public static function provideUrl(): \Generator
    {
        yield 'homepage with ending slash' => [
            'https://example.com/',
        ];

        yield 'homepage without ending slash' => [
            'https://example.com',
        ];

        yield 'page url 1' => [
            'https://example.com/page1/',
        ];

        yield 'page url 2' => [
            'https://example.com/page2/lorem-ipsum/',
        ];

        yield 'page url with query parameters 1' => [
            'https://example.com/page2/lorem-ipsum?test1&test2',
        ];

        yield 'page url with query parameters 2' => [
            'https://example.com/page2/lorem-ipsum?test1=123&test2=abc',
        ];
    }

    public static function provideUrlWithTokens(): \Generator
    {
        yield 'homepage with ending slash' => [
            'https://example.com/?{formfield=abc}&{contactfield=abc123}',
            'https://example.com/?formfield-1&contactfield-2',
        ];

        yield 'all supported tokens' => [
            '{pagelink=123}?{formfield=abc}&{contactfield=abc123}',
            'https://example.com?formfield-2&contactfield-3',
        ];

        yield 'page url 1' => [
            '{pagelink=123}/page1?{formfield=abc}&{contactfield=abc123}',
            'https://example.com/page1?formfield-2&contactfield-3',
        ];

        yield 'page url 2' => [
            '{pagelink=123}/page2/lorem-ipsum/?{formfield=abc}&{contactfield=abc123}',
            'https://example.com/page2/lorem-ipsum/?formfield-2&contactfield-3',
        ];

        yield 'page url with query parameters 1' => [
            '{pagelink=123}/page2/lorem-ipsum?test1&test2&{formfield=abc}&{contactfield=abc123}',
            'https://example.com/page2/lorem-ipsum?test1&test2&formfield-2&contactfield-3',
        ];

        yield 'page url with query parameters 2' => [
            '{pagelink=123}/page2/lorem-ipsum?test1=123&test2=abc&{formfield=abc}&{contactfield=abc123}',
            'https://example.com/page2/lorem-ipsum?test1=123&test2=abc&formfield-2&contactfield-3',
        ];

        yield 'page url with query parameters - multiple same tokens' => [
            '{pagelink=123}/page2/lorem-ipsum?test1=123&test2=abc&{formfield=abc}&{formfield=def}&{contactfield=abc123}&{contactfield=def456}',
            'https://example.com/page2/lorem-ipsum?test1=123&test2=abc&formfield-2&formfield-3&contactfield-4&contactfield-5',
        ];

        yield 'page url without query parameters' => [
            '{pagelink=123}/page2/{formfield=abc}/lorem-ipsum/{contactfield=abc123}',
            'https://example.com/page2/formfield-2/lorem-ipsum/contactfield-3',
        ];
    }

    public function testNotSupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('lorem ipsum', new NotBlank());
    }

    #[DataProvider('provideEmptyValue')]
    public function testEmptyValue(?string $emptyValue): void
    {
        $this->validator->validate($emptyValue, new IsPostActionRedirectUrl());
        $this->assertNoViolation();
    }

    #[DataProvider('provideIncorrectUrl')]
    public function testIncorrectUrl(string $incorrectUrl, ?string $dummyDataUrl): void
    {
        $violationParameters = [
            '{{ value }}' => $incorrectUrl,
        ];

        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getMessage')->willReturn('Incorrect URL message');
        $violation->method('getParameters')->willReturn($violationParameters);

        $violationList = new ConstraintViolationList([$violation]);
        $urlConstraint = new Url(message: 'mautic.form.form.postactionproperty_redirect.url');

        $this
            ->urlValidator
            ->expects(self::once())
            ->method('validate')
            ->with($dummyDataUrl ?? $incorrectUrl, $urlConstraint)
            ->willReturn($violationList);

        $this->validator->validate($incorrectUrl, new IsPostActionRedirectUrl());

        $this
            ->buildViolation('Incorrect URL message')
            ->setParameters($violationParameters)
            ->assertRaised();
    }

    #[DataProvider('provideUrl')]
    public function testRegularUrl(string $url): void
    {
        $this->validator->validate($url, new IsPostActionRedirectUrl());
        $this->assertNoViolation();
    }

    #[DataProvider('provideUrlWithTokens')]
    public function testUrlWithTokens(string $url, string $dummyDataUrl): void
    {
        $violationList = new ConstraintViolationList();
        $urlConstraint = new Url(message: 'mautic.form.form.postactionproperty_redirect.url');

        $this
            ->urlValidator
            ->expects(self::once())
            ->method('validate')
            ->with($dummyDataUrl, $urlConstraint)
            ->willReturn($violationList);

        $this->validator->validate($url, new IsPostActionRedirectUrl());
        $this->assertNoViolation();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->urlValidator      = $this->createMock(ValidatorInterface::class);
        $redirectUrlTokensFinder = new RedirectUrlTokensFinder();

        return new IsPostActionRedirectUrlValidator($this->urlValidator, $redirectUrlTokensFinder);
    }
}
