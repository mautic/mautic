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

class MonitorTwitterHashtagsCommand extends MonitorTwitterBaseCommand
{
    /*
     * Configure the command, set name and options.
     */
    protected function configure()
    {
        $this->setName('social:monitor:twitter:hashtags')
            ->setDescription('Looks at our monitoring records and finds hashtags');

        parent::configure();
    }

    /**
     * Search for tweets by hashtag.
     *
     * @param $monitor
     *
     * @return bool
     */
    protected function getTweets($monitor)
    {
        $params = $monitor->getProperties();

        $stats = $monitor->getStats();

        // build hashtag search url
        $searchUrl = $this->twitter->getApiUrl('search/tweets');

        if (!array_key_exists('hashtag', $params)) {
            $this->output->writeln('No hashtag was found!');

            return false;
        }

        $query = $this->buildTwitterSearchQuery(
            [
                '#'.$params['hashtag'],
            ]
        );

        // @todo set up count to be configurable
        $requestQuery = [
            'q'     => $query,
            'count' => $this->queryCount,
        ];

        // if we have a max id string use it here
        if (is_array($stats) && array_key_exists('max_id_str', $stats) && $stats['max_id_str']) {
            $requestQuery['since_id'] = $stats['max_id_str'];
        }

        $results = $this->twitter->makeRequest($searchUrl, $requestQuery);

        return $results;
    }

    public function getNetworkName()
    {
        return 'twitter';
    }
}
