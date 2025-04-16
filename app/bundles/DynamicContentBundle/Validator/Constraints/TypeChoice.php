<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Validator\Constraints;

use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use Symfony\Component\Validator\Constraints\Choice;

final class TypeChoice extends Choice
{
    /**
     * @var string
     */
    public const ERROR_MESSAGE = '{{ value }} is an invalid type. Allowed values are [%s, %s]';

    /**
     * @var string
     */
    public $message;

    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->message = sprintf(self::ERROR_MESSAGE, TypeList::HTML, TypeList::TEXT);
    }
}
