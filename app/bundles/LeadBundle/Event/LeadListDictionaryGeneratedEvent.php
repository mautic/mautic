<?php

/*
 * @copyright  2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class LeadListDictionaryGeneratedEvent extends Event
{
    /**
     * @var array
     */
    private $translations;

    public function __construct($translations)
    {
        $this->translations = $translations;
    }

    /**
     * @param $key
     * @param array $options
     */
    public function addTranslation($key, $options)
    {
        $this->translations[$key]= $options;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param array $translations
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }
}
