<?php

namespace MauticPlugin\MauticSocialBundle\Command;

use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'social:monitor:twitter:hashtags',
    description: 'Looks at our monitoring records and finds hashtags'
)]
class MonitorTwitterHashtagsCommand extends MonitorTwitterBaseCommand
{
    /**
     * Search for tweets by hashtag.
     *
     * @param Monitoring $monitor
     *
     * @return bool|array False if missing the hashtag, otherwise the array response from Twitter
     */
    protected function getTweets($monitor)
    {
        $params = $monitor->getProperties();
        $stats  = $monitor->getStats();

        if (!array_key_exists('hashtag', $params)) {
            $this->output->writeln('No hashtag was found!');

            return false;
        }

        $searchUrl    = $this->twitter->getApiUrl('search/tweets');
        $requestQuery = [
            'q'     => '#'.$params['hashtag'],
            'count' => $this->queryCount,
        ];

        // if we have a max id string use it here
        if (is_array($stats) && array_key_exists('max_id_str', $stats) && $stats['max_id_str']) {
            $requestQuery['since_id'] = $stats['max_id_str'];
        }

        return $this->twitter->makeRequest($searchUrl, $requestQuery);
    }

    public function getNetworkName(): string
    {
        return 'twitter';
    }
}
