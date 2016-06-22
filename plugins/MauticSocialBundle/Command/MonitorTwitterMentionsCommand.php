<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Command;

class MonitorTwitterMentionsCommand extends MonitorTwitterBaseCommand
{
    protected function configure()
    {
        $this->setName('social:monitor:twitter:mentions')
            ->setDescription('Searches for mentioned tweets');

        parent::configure();
    }

    /*
     * Search for tweets by mention
     */
    public function getTweets($monitor)
    {
        // monitor params
        $params = $monitor->getProperties();

        // stats
        $stats = $monitor->getStats();

        if (!array_key_exists('handle', $params)) {
            $this->output->writeln('no handle was found!');
            exit();
        }

        // build mentions url
        $mentionsUrl = $this->twitter->getApiUrl('search/tweets');

        $query = $this->buildTwitterSearchQuery(
            array(
                '@'.$params['handle']
            )
        );

        // @todo set up count to be configurable
        $requestQuery = array(
            'q'     => $query,
            'count' => $this->queryCount
        );

        // if we have a max id string use it here
        if (is_array($stats) && array_key_exists('max_id_str', $stats) && $stats['max_id_str']) {
            $requestQuery['since_id'] = $stats['max_id_str'];
        }

        $results = $this->twitter->makeRequest($mentionsUrl, $requestQuery);

        return $results;
    }

    public function getNetworkName()
    {
        return 'twitter';
    }
}