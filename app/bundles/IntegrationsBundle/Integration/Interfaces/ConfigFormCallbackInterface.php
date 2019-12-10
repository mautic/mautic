<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

interface ConfigFormCallbackInterface
{
    /**
     * Message ID used in form as description what for is used callback URL.
     *
     * @return string
     */
    public function getCallbackHelpMessageTranslationKey(): string;
}
