<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Exception;

/**
 * An imported form or form field has an alias that can't be used as a table or column name.
 */
final class InvalidImportAliasException extends \RuntimeException
{
}
