<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Command;

use MauticPlugin\MauticSocialBundle\Entity\Monitoring;

class MonitorTwitterMentionsCommand extends MonitorTwitterBaseCommand
{
    /**
     * Configure the command, set name and options.
     */
    protected function configure()
    {
        $this->setName('social:monitor:twitter:mentions')
            ->setDescription('Searches for mentioned tweets');

        parent::configure();
    }

    /**
     * Search for tweets by mention.
     *
     * @param Monitoring $monitor
     *
     * @return bool|array False if missing the twitter handle, otherwise the array response from Twitter
     */
    protected function getTweets($monitor)
    {
        $params = $monitor->getProperties();
        $stats  = $monitor->getStats();

        if (!array_key_exists('handle', $params)) {
            $this->output->writeln('No twitter handle was found!');

            return false;
        }

        $mentionsUrl  = $this->twitter->getApiUrl('search/tweets');
        $requestQuery = [
            'q'     => '@'.$params['handle'],
            'count' => $this->queryCount,
        ];

        // if we have a max id string use it here
        if (is_array($stats) && array_key_exists('max_id_str', $stats) && $stats['max_id_str']) {
            $requestQuery['since_id'] = $stats['max_id_str'];
        }

        return $this->twitter->makeRequest($mentionsUrl, $requestQuery);
    }

    public function getNetworkName()
    {
        return 'twitter';
    }
}
