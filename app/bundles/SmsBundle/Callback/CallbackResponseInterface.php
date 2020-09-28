<?php

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Callback;

/**
 * Needed for ReplyHelperTest, please do not use in Integrations!
 */
interface CallbackResponseInterface extends CallbackInterface, ResponseInterface
{
}
