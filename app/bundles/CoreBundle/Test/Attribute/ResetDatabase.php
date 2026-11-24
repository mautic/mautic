<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Attribute;

/**
 * Marks a MauticMysqlTestCase test that needs a full database reset instead of transaction rollback,
 * e.g. because it alters the schema (custom fields) or re-creates the client.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ResetDatabase
{
}
