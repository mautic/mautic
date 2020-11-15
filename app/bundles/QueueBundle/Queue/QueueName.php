<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Queue;

/**
 * Class QueueName.
 */
final class QueueName
{
    const EMAIL_HIT         = 'email_hit';
    const PAGE_HIT          = 'page_hit';
    const TRANSPORT_WEBHOOK = 'transport_webhook';
}
