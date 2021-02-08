<?php

/*
 * @copyright  2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\AbstractCustomRequestEvent;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class LeadListFiltersChoicesEvent.
 */
class LeadListFiltersChoicesEvent extends AbstractCustomRequestEvent
{
    /**
     * Please refer to ListModel.php, inside getChoiceFields method, for examples of choices.
     *
     * @var array
     */
    protected $choices;

    /**
     * Please refer to ListModel.php, inside getChoiceFields method, for default operators availabled.
     *
     * @var array
     */
    protected $operators;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param array $choices
     * @param array $operators
     */
    public function __construct($choices, $operators, TranslatorInterface $translator, Request $request = null)
    {
        parent::__construct($request);

        $this->choices    = $choices;
        $this->operators  = $operators;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Add a new choice for list filters
     * Please refer to ListModel.php, inside getChoiceFields method, for examples of choices.
     *
     * @param string $object
     * @param string $choiceKey
     * @param array  $choiceConfig
     */
    public function addChoice($object, $choiceKey, $choiceConfig)
    {
        if (!isset($this->choices[$object])) {
            $this->choices[$object] = [];
        }

        if (!array_key_exists($choiceKey, $this->choices[$object])) {
            $this->choices[$object][$choiceKey] = $choiceConfig;
        }
    }
}
