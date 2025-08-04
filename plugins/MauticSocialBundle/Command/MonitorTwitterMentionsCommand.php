<?php

namespace MauticPlugin\MauticSocialBundle\Command;

use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'social:monitor:twitter:mentions',
    description: 'Searches for mentioned tweets'
)]
class MonitorTwitterMentionsCommand extends MonitorTwitterBaseCommand
{
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

    public function getNetworkName(): string
    {
        return 'twitter';
    }
}
