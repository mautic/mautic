<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ImagePathValidator extends ConstraintValidator
{
    public function __construct(private readonly \Mautic\CoreBundle\Helper\PathsHelper $pathsHelper)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$value || !is_string($value)) {
            return;
        }

        $fileName = trim($value);

        // Decode a few times to catch nested encodings like %252e%252e
        for ($i = 0; $i < 3; ++$i) {
            $decoded = rawurldecode($fileName);
            if ($decoded === $fileName) {
                break;
            }
            $fileName = $decoded;
        }

        // Basic sanity: no control chars or null bytes
        if (preg_match('/[\x00-\x1F\x7F]/', $fileName)) {
            $this->context->buildViolation('mautic.lead.field.companylogo_filename.invalid')->addViolation();

            return;
        }

        // Forbid any path separators or drive letters
        if (preg_match('#[\\\\/]#', $fileName) || preg_match('/^[A-Za-z]:/', $fileName)) {
            $this->context->buildViolation('mautic.lead.field.companylogo_filename.invalid')->addViolation();

            return;
        }

        // Forbid traversal or hidden filenames
        if (str_contains($fileName, '..') || str_starts_with($fileName, '.')) {
            $this->context->buildViolation('mautic.lead.field.companylogo_filename.invalid')->addViolation();

            return;
        }

        $rootFilePath = sprintf(
            '%s%smedia%slogos%s%s',
            $this->pathsHelper->getRootPath(),
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $fileName
        );

        // Ensure the resolved path is within the logos directory
        if (!file_exists($rootFilePath)) {
            $this->context->buildViolation('mautic.lead.field.companylogo_filename.invalid')
                ->addViolation();

            return;
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $pathInfo          = pathinfo(parse_url($rootFilePath, PHP_URL_PATH));
        $extension         = strtolower($pathInfo['extension'] ?? '');

        // Remove any query parameters from the URL
        $parts = parse_url($rootFilePath);
        //        dd($parts);

        if (array_key_exists('query', $parts) && is_string($parts['query']) && '' !== $parts['query']) {
            $this->context->buildViolation('mautic.lead.field.companylogo_filename.invalid')
                ->addViolation();

            return;
        }

        if (!in_array($extension, $allowedExtensions, true)) {
            $this->context->buildViolation('mautic.lead.field.companylogo_filename.invalid')
                ->addViolation();

            return;
        }

        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($rootFilePath) ?: '';
        } catch (\Throwable) {
            $this->context->buildViolation('mautic.lead.field.companylogo_filename.invalid')
                ->addViolation();

            return;
        }

        if (
            '' !== $fileName
            && ('' === $mime || !preg_match('#^image/(jpeg|png|jpg)$#', $mime))
        ) {
            $this->context->buildViolation('mautic.lead.field.companylogo_filename.invalid')
                ->addViolation();
        }
    }
}
