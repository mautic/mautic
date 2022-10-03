<?php

declare(strict_types=1);

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

    private string $search;

    /**
     * @param mixed[] $choices
     * @param mixed[] $operators
     */
    public function __construct($choices, $operators, TranslatorInterface $translator, Request $request = null, string $search = '')
    {
        parent::__construct($request);

        $this->choices    = $choices;
        $this->operators  = $operators;
        $this->translator = $translator;
        $this->search     = $search;
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

    public function getSearch(): string
    {
        return $this->search;
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
