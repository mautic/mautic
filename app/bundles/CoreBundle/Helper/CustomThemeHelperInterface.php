<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Marks installed themes as custom themes. Custom themes show in the Custom Theme tab of the email editor.
 */
interface CustomThemeHelperInterface
{
    /**
     * Marks a theme as custom. If the theme is already custom, the mark is removed.
     *
     * @param string $themeName The exact directory name of an installed theme
     *
     * @return bool true if the theme is custom after the change
     *
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function toggleCustom(string $themeName): bool;
}
