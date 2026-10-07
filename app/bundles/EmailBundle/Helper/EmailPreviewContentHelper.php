<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\DTO\EmailPreviewContentResult;

final readonly class EmailPreviewContentHelper
{
    public function __construct(
        private ThemeHelper $themeHelper,
    ) {
    }

    public function resolve(Email $email, ?string $content = null, bool $contentProvided = false): EmailPreviewContentResult
    {
        if (!$contentProvided) {
            $content = (string) ($email->getCustomHtml() ?? '');
        } else {
            $content ??= '';
        }

        if ('' !== trim($content)) {
            return new EmailPreviewContentResult($content, false);
        }

        $template = $email->getTemplate();
        if (!$template || 'mautic_code_mode' === $template) {
            return new EmailPreviewContentResult('', false);
        }

        $themeContent = $email->getContent();
        if (!is_array($themeContent)) {
            $themeContent = [];
        }

        $logicalName = $this->themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/email.html.twig');

        $rendered = $this->themeHelper->renderThemeTemplate(
            $logicalName,
            [
                'inBrowser' => true,
                'content'   => $themeContent,
                'email'     => $email,
                'lead'      => null,
                'template'  => $template,
            ]
        );

        return new EmailPreviewContentResult($rendered, true);
    }
}
