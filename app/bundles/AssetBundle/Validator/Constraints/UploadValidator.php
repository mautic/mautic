<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Validator\Constraints;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UploadValidator extends ConstraintValidator
{
    public function __construct(
        private FileUploadValidator $fileUploadValidator,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof Asset) {
            throw new UnexpectedTypeException($value, Asset::class);
        }

        if (!$constraint instanceof Upload) {
            throw new UnexpectedTypeException($constraint, Upload::class);
        }

        if ($value->isLocal()) {
            $this->validateLocal($value);
            $value->setRemotePath(null);

            return;
        }

        if ($value->isRemote()) {
            $this->validateRemote($value);
            $value->setPath(null);
        }
    }

    private function validateLocal(Asset $asset): void
    {
        if ($asset->isNew() && null === $asset->getTempName() && null === $asset->getPath()) {
            $this->context->buildViolation('mautic.asset.asset.error.missing.file')
                ->atPath('tempName')
                ->addViolation();
        }

        if (null === $asset->getTitle()) {
            $this->context->buildViolation('mautic.asset.asset.error.missing.title')
                ->atPath('title')
                ->addViolation();
        }

        $this->validateExtensionAndMimeType($asset->getExtension(), $asset->loadFile())
            && $this->validateExtensionAndMimeType($this->parseExtension($asset->getTempName()), $asset->loadFile(true))
            && $this->validateExtensionAndMimeType($this->parseExtension($asset->getOriginalFileName()), null);
    }

    private function validateRemote(Asset $asset): void
    {
        if (null === $asset->getRemotePath()) {
            $this->context->buildViolation('mautic.asset.asset.error.missing.remote.path')
                ->atPath('remotePath')
                ->addViolation();
        }

        $this->validateMimeType($asset->getRemoteMimeTypeFromHeader())
            && $this->validateMimeType($asset->getRemoteMimeTypeFromMagicBytes());
    }

    private function parseExtension(?string $fileName): ?string
    {
        if (null === $fileName) {
            return null;
        }

        return pathinfo($fileName, PATHINFO_EXTENSION);
    }

    private function validateExtensionAndMimeType(?string $extension, ?File $file): bool
    {
        if (null === $extension) {
            return true;
        }

        try {
            $this->fileUploadValidator->checkExtension($extension, $this->getAllowedExtensions());
        } catch (FileInvalidException $e) {
            $this->addViolation($e);

            return false;
        }

        if ($file && ($mimeType = $file->getMimeType())) {
            try {
                $this->fileUploadValidator->checkMimeTypesMatchExtension([$mimeType], $extension);
            } catch (FileInvalidException $e) {
                $this->addViolation($e);

                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function getAllowedExtensions(): array
    {
        return $this->coreParametersHelper->get('allowed_extensions');
    }

    private function validateMimeType(string $mimeType): bool
    {
        if (!$mimeType) {
            $this->context->buildViolation('mautic.asset.asset.error.remote.mimetype.not.resolved')
                ->atPath('file')
                ->addViolation();

            return false;
        }

        try {
            $this->fileUploadValidator->checkMimeType($mimeType, $this->getAllowedExtensions());

            return true;
        } catch (FileInvalidException $e) {
            $this->addViolation($e);

            return false;
        }
    }

    private function addViolation(FileInvalidException $e): void
    {
        $this->context->buildViolation($e->getMessageId(), $e->getParameters())
            ->atPath('file')
            ->addViolation();
    }
}
