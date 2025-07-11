<?php

namespace Mautic\FormBundle\Tests\Validator;

use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Exception\FileValidationException;
use Mautic\FormBundle\Exception\NoFileGivenException;
use Mautic\FormBundle\Validator\UploadFieldValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request;

#[\PHPUnit\Framework\Attributes\CoversClass(UploadFieldValidator::class)]
class UploadFieldValidatorTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('No Files given')]
    public function testNoFilesGiven(): void
    {
        $fileUploadValidatorMock = $this->createMock(FileUploadValidator::class);

        $fileUploadValidatorMock->expects($this->never())
            ->method('validate');

        $parameterBagMock = $this->createMock(FileBag::class);

        $parameterBagMock->expects($this->once())
            ->method('get')
            ->with('mauticform')
            ->willReturn(false);

        $request        = new Request();
        $request->files = $parameterBagMock;

        $fileUploadValidator = new UploadFieldValidator($fileUploadValidatorMock);

        $field = new Field();

        $this->expectException(NoFileGivenException::class);

        $fileUploadValidator->processFileValidation($field, $request);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Exception should be thrown when validation fails')]
    public function testValidationFailed(): void
    {
        $fileUploadValidatorMock = $this->createMock(FileUploadValidator::class);

        $fileUploadValidatorMock->expects($this->once())
            ->method('validate')
            ->willThrowException(new FileInvalidException('Validation failed'));

        $parameterBagMock = $this->createMock(FileBag::class);

        $fileMock = $this->createMock(UploadedFile::class);

        $files = [
            'file' => $fileMock,
        ];

        $parameterBagMock->expects($this->once())
            ->method('get')
            ->with('mauticform')
            ->willReturn($files);

        $request        = new Request();
        $request->files = $parameterBagMock;

        $fileUploadValidator = new UploadFieldValidator($fileUploadValidatorMock);

        $field = new Field();
        $field->setAlias('file');
        $field->setProperties([
            'allowed_file_size'       => 1,
            'allowed_file_extensions' => ['jpg', 'gif'],
        ]);

        $this->expectException(FileValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        $fileUploadValidator->processFileValidation($field, $request);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('No validation error')]
    public function testFileIsValid(): void
    {
        $fileUploadValidatorMock = $this->createMock(FileUploadValidator::class);

        $fileUploadValidatorMock->expects($this->once())
            ->method('validate');

        $parameterBagMock = $this->createMock(FileBag::class);

        $fileMock = $this->createMock(UploadedFile::class);

        $files = [
            'file' => $fileMock,
        ];

        $parameterBagMock->expects($this->once())
            ->method('get')
            ->with('mauticform')
            ->willReturn($files);

        $request        = new Request();
        $request->files = $parameterBagMock;

        $fileUploadValidator = new UploadFieldValidator($fileUploadValidatorMock);

        $field = new Field();
        $field->setAlias('file');
        $field->setProperties([
            'allowed_file_size'       => 1,
            'allowed_file_extensions' => ['jpg', 'gif'],
        ]);

        $file = $fileUploadValidator->processFileValidation($field, $request);

        $this->assertSame($fileMock, $file);
    }
}
