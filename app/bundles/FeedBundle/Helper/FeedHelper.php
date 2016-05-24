<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\Helper;

use Debril\RssAtomBundle\Protocol\Parser\Factory;
use Debril\RssAtomBundle\Protocol\Parser\XmlParser;
use Debril\RssAtomBundle\Protocol\FeedReader;

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
     *
     * @return FeedInInterface|FeedContent
     */
    public function getFeedContentFromString($xmlString) {
        // Parses the contents into a SimpleXMLElement
        $xmlBody = $this->xmlParser->parseString($xmlString);

        // Finds the appropriate parser for the given feed
        $parser = $this->reader->getAccurateParser($xmlBody);

        // Parses the feed with the correct parser
        $feedContent = $parser->parse($xmlBody, $this->factory->newFeed());

        return $feedContent;
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
