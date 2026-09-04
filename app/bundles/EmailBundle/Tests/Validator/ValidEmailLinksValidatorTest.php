<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Validator;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Validator\ValidEmailLinks;
use Mautic\EmailBundle\Validator\ValidEmailLinksValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

#[AllowMockObjectsWithoutExpectations]
final class ValidEmailLinksValidatorTest extends TestCase
{
    private ExecutionContextInterface&\PHPUnit\Framework\MockObject\MockObject $context;

    private ValidEmailLinksValidator $validator;

    protected function setUp(): void
    {
        $this->context   = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new ValidEmailLinksValidator();
        $this->validator->initialize($this->context);
    }

    public function testMalformedCustomHtmlLinkAddsViolation(): void
    {
        $email = new Email();
        $email->setCustomHtml('<a href="://example.com">Broken link</a>');

        $this->expectViolation('customHtml', '://example.com');

        $this->validator->validate($email, new ValidEmailLinks());
    }

    public function testMalformedDynamicContentLinkAddsViolation(): void
    {
        $email = new Email();
        $email->setContent(['slot' => ['content' => '<a href="://example.com">Broken link</a>']]);

        $this->expectViolation('content', '://example.com');

        $this->validator->validate($email, new ValidEmailLinks());
    }

    public function testCustomHtmlTakesPrecedenceOverStaleBuilderContent(): void
    {
        $email = new Email();
        $email->setCustomHtml('<a href="https://example.com">Valid link</a>');
        $email->setContent(['slot' => ['content' => '<a href="://example.com">Stale broken link</a>']]);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($email, new ValidEmailLinks());
    }

    #[DataProvider('validLinkProvider')]
    public function testValidLinksDoNotAddViolation(string $url): void
    {
        $email = new Email();
        $email->setCustomHtml(sprintf('<a href="%s">Valid link</a>', $url));

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($email, new ValidEmailLinks());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validLinkProvider(): iterable
    {
        yield 'absolute URL' => ['https://example.com/path?foo=bar'];
        yield 'URL with a token' => ['https://example.com/path?{contactfield=customfield-a}={contactfield=customfield-b}&array[]=value1#footer'];
        yield 'mailto URL' => ['mailto:test@example.com'];
        yield 'Mautic token' => ['{unsubscribe_url}'];
    }

    public function testUnexpectedConstraintThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Email(), $this->createStub(Constraint::class));
    }

    private function expectViolation(string $path, string $url): void
    {
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('%url%', $url)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with($path)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.email.links.invalid')
            ->willReturn($violationBuilder);
    }
}
