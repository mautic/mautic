<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Exception;

/**
 * Thrown when an email uses an MJML theme but has no compiled customHtml.
 * This is a bad state: GrapesJS should compile the MJML theme into customHtml
 * client-side. Saving or sending without customHtml would deliver uncompiled
 * <mjml> markup (or an empty body) to recipients.
 */
final class MjmlThemeEmptyCustomHtmlException extends \RuntimeException
{
}
