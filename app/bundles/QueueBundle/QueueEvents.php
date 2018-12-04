<?php

/*
 * @copyright   Mautic, Inc
 * @author      Mautic, Inc
 *
 * @link        http://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle;

/**
 * Class MauticQueueEvents
 * Events available for MauticQueueBundle.
 */
final class QueueEvents
{
    const CONSUME_MESSAGE = 'mautic.queue_consume_message';

    const PUBLISH_MESSAGE = 'mautic.queue_publish_message';

    const BUILD_CONFIG = 'mautic.queue_build_config';

    const EMAIL_HIT = 'mautic.queue_email_hit';

    const PAGE_HIT = 'mautic.queue_page_hit';

    const TRANSPORT_WEBHOOK = 'mautic.queue_transport_webhook';
}
