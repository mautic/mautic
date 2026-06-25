<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Form\Validator\Constraints;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\SmsBundle\Entity\Sms;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class MediaMaxAllowedSizeValidator extends ConstraintValidator
{
    private const MAX_MEDIA_SIZE_IN_BYTES = 5000000;

    public function __construct(private AssetsHelper $assetsHelper, private PathsHelper $pathsHelper)
    {
    }

    /**
     * @param Sms $sms
     */
    public function validate($sms, Constraint $constraint): void
    {
        if (!$constraint instanceof MediaMaxAllowedSize) {
            throw new UnexpectedTypeException($constraint, MediaMaxAllowedSize::class);
        }

        if (empty($sms->getMedia())) {
            return;
        }

        $totalMediaSize = 0;
        $baseHost       = parse_url($this->assetsHelper->getBaseUrl(), PHP_URL_HOST);

        foreach ($sms->getMedia() as $media) {
            $pathInfo = parse_url($media);
            if (!is_array($pathInfo) || empty($pathInfo['path'])) {
                continue;
            }

            $host = $pathInfo['host'] ?? $baseHost;
            $path = $pathInfo['path'];
            if ($baseHost !== $host) {
                // skip because it is a third party url.
                continue;
            }

            if (!str_starts_with($path, '/')) {
                $path = '/'.$path;
            }

            $filePath = $this->pathsHelper->getSystemPath('local_root').$path;
            if (file_exists($filePath)) {
                $totalMediaSize += filesize($filePath);
            }
        }
        // total media should not be more than 5MB
        if ($totalMediaSize > self::MAX_MEDIA_SIZE_IN_BYTES) {
            $this->context->buildViolation($constraint->message)
                ->atPath('media')
                ->addViolation();
        }
    }
}
