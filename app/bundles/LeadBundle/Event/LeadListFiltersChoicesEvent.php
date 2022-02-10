<?php

declare(strict_types=1);

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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class LeadListFiltersChoicesEvent extends AbstractCustomRequestEvent
{
    /**
     * Please refer to ListModel.php, inside getChoiceFields method, for examples of choices.
     *
     * @var mixed
     */
    protected $choices;

    /**
     * Please refer to ListModel.php, inside getChoiceFields method, for default operators availabled.
     *
     * @var mixed[]
     */
    protected $operators;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param mixed[] $choices
     * @param mixed[] $operators
     */
    public function __construct($choices, $operators, TranslatorInterface $translator, Request $request = null)
    {
        parent::__construct($request);

        $this->choices    = $choices;
        $this->operators  = $operators;
        $this->translator = $translator;
    }

    /**
     * @return mixed[]
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @return mixed[]
     */
    public function getOperators()
    {
        return $this->operators;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Add a new choice for list filters
     * Please refer to ListModel.php, inside getChoiceFields method, for examples of choices.
     *
     * @param string  $object
     * @param string  $choiceKey
     * @param mixed[] $choiceConfig
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

    /**
     * @param mixed[] $choiceConfig
     */
    public function setChoice(string $object, string $choiceKey, array $choiceConfig): void
    {
        if (!isset($this->choices[$object])) {
            $this->choices[$object] = [];
        }

        $this->choices[$object][$choiceKey] = $choiceConfig;
    }

    /**
     * @param array<string,array<string,string>> $choices
     */
    public function setChoices(array $choices): void
    {
        $this->choices = $choices;
    }
}
