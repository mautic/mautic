<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\Helper;

use Debril\RssAtomBundle\Protocol\FeedInterface;
use Debril\RssAtomBundle\Protocol\FeedReader;
use Debril\RssAtomBundle\Protocol\Parser\Factory;
use Debril\RssAtomBundle\Protocol\Parser\XmlParser;
use Mautic\FeedBundle\Entity\Feed;
use Debril\RssAtomBundle\Protocol\ItemOutInterface;
use Debril\RssAtomBundle\Protocol\Filter\Limit;

class FeedHelper
{

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var XmlParser
     */
    protected $xmlParser;

    /**
     * @var FeedReader
     */
    protected $reader;

    /**
     * Parse an xml string and create a FeedInInterface instance
     *
     * @param string $xmlString
     * @param integer|null $itemCount
     *
     * @return FeedInterface
     */
    public function getFeedContentFromString($xmlString, $itemCount=null)
    {
        // Parses the contents into a SimpleXMLElement
        $xmlBody = $this->xmlParser->parseString($xmlString); // TODO il manque la gestion des erreurs en cas d'objet XML non conforme

        // Finds the appropriate parser for the given feed
        $parser = $this->reader->getAccurateParser($xmlBody);

        // Parses the feed with the correct parser
        if (is_numeric($itemCount)) {
            $feedContent = $parser->parse($xmlBody, $this->factory->newFeed(), array(
                new Limit($itemCount)
            ));
        } else {
            $feedContent = $parser->parse($xmlBody, $this->factory->newFeed());
        }

        return $feedContent;
    }

    /**
     * @param string $feedUrl
     *
     * @return null|string
     */
    public function getStringFromFeed($feedUrl)
    {
        $response = $this->reader->getResponse($feedUrl);
        if ($response->getHttpCode() === 200) {
            return $response->getBody();
        } else {
            return null;
        }
    }

    /**
     * Populates an array with the feed's content
     *
     * @param FeedInterface $feed
     *
     * @return array
     */
    public function getFeedFields(FeedInterface $feed)
    {
        return array(
            'feedtitle' => $feed->getTitle(),
            'feeddescription' => $feed->getDescription(),
            'feedlink' => $feed->getLink(),
            'feeddate' => $feed->getLastModified()->format('Y-m-d H:i:s'),
            '*items' => $this->getItemFields($feed->getItems())
        );
    }

    /**
     * Populates an array with the items' contents
     *
     * @param ItemOutInterface[] $items
     *
     * @return array
     */
    public function getItemFields($items) {
        $itemFields = array();
        foreach ($items as $item) {
            $itemFields[] = array(
                'itemtitle' => $item->getTitle(),
                'itemdescription' => $item->getDescription(),
                'itemauthor' => $item->getAuthor(),
                'itemsummary' => $item->getSummary(),
                'itemlink' => $item->getLink(),
                'itemdate' => $item->getUpdated()->format('Y-m-d H:i:s')
            );
        }
        return $itemFields;
    }

    /**
     * Unfolds the feed loop
     *
     * @param array  $feed
     * @param string $content
     */
    public function unfoldFeedItems($feed, $content) {
        // Get the string to replicate
        $matches = array();
        $unfoldedContent = '';
        $hasMatched = preg_match_all('/\{feed=loopstart\}(.*)\{feed=loopend\}/s', $content, $matches);

        if ($hasMatched) {
            $inner = $matches[1][0];
            // Create the replicas
            $count = count($feed['*items']);
            for ($i = 0; $i < $count; ++$i) {
                $unfoldedContent .= preg_replace('/\{feedfield=item(\w+)\}/s', '{feedfield=item$1#'.$i.'}', $inner);
            }
        }

        // Replace with the final tags
        return str_replace($matches[0], $unfoldedContent, $content);
    }

    /**
     * Turns the bidimentional array of item fields into a numbered
     * unidimentional array
     *
     * @param array $items
     * @return array
     */
    public function flattenItems($items) {
        $flatItems = array();
        foreach ($items as $index => $item) {
            foreach ($item as $field => $content) {
                $flatItems["$field#$index"] = $content;
            }
        }
        return $flatItems;
    }

    /**
     * Extracts the inner items array's contents into the same array as the
     * feed fields
     *
     * @param array $feed
     * @return array
     */
    public function flattenFeed($feed) {
        $flatFeed = array_merge($feed, $this->flattenItems($feed['*items']));
        unset($flatFeed['*items']);
        return $flatFeed;
    }

    public function setFactory(Factory $factory)
    {
        $this->factory = $factory;
        return $this;
    }

    public function setXmlParser(XmlParser $xmlParser)
    {
        $this->xmlParser = $xmlParser;
        return $this;
    }

    public function setReader(FeedReader $reader)
    {
        $this->reader = $reader;
        return $this;
    }


}
