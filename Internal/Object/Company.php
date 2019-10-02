<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Internal\Object;

final class Company implements ObjectInterface
{
    const NAME = 'company';

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return self::NAME;
    }
}
