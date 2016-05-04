<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Joomla\Http\Http;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\SmsEvents;

/**
 * Class CampaignSubscriber
 *
 * @package MauticSmsBundle
 */
class SmsSubscriber extends CommonSubscriber
{
    /**
     * @var string
     */
    protected $urlRegEx = '/https?\:\/\/([a-zA-Z0-9\-\.]+\.[a-zA-Z]+(\.[a-zA-Z])?)(\/\S*)?/i';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            SmsEvents::SMS_ON_SEND => array('onSmsSend', 0)
        );
    }

    /**
     * @param SmsSendEvent $event
     */
    public function onSmsSend(SmsSendEvent $event)
    {
        $content = $event->getContent();
        $tokens = array();
        /** @var \Mautic\SmsBundle\Api\AbstractSmsApi $smsApi */
        $smsApi = $this->factory->getKernel()->getContainer()->get('mautic.sms.api');

        if ($this->contentHasLinks($content)) {
            preg_match_all($this->urlRegEx, $content, $matches);

            foreach ($matches[0] as $url) {
                $tokens[$url] = $smsApi->convertToTrackedUrl(
                    $url,
                    array(
                        'sms'  => $event->getSmsId(),
                        'lead' => $event->getLead()->getId()
                    )
                );
            }
        }

        $content = str_ireplace(array_keys($tokens), array_values($tokens), $content);

        $event->setContent($content);
    }

    /**
     * Check string for links
     *
     * @param string $content
     *
     * @return bool
     */
    protected function contentHasLinks($content)
    {
        return preg_match($this->urlRegEx, $content);
    }
}