<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper;

use Mautic\EmailBundle\MonitoredEmail\Exception\CategoryNotFound;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper\Category as CategoryObject;

class CategoryMapper
{
    /**
     * @var array
     */
    protected static $mappings = [
        Category::ANTISPAM       => ['permanent' => false, 'bounce_type' => Type::BLOCKED],
        Category::AUTOREPLY      => ['permanent' => false, 'bounce_type' => Type::AUTOREPLY],
        Category::CONCURRENT     => ['permanent' => false, 'bounce_type' => Type::SOFT],
        Category::CONTENT_REJECT => ['permanent' => false, 'bounce_type' => Type::SOFT],
        Category::COMMAND_REJECT => ['permanent' => true, 'bounce_type' => Type::HARD],
        Category::INTERNAL_ERROR => ['permanent' => false, 'bounce_type' => Type::TEMPORARY],
        Category::DEFER          => ['permanent' => false, 'bounce_type' => Type::SOFT],
        Category::DELAYED        => ['permanent' => false, 'bounce_type' => Type::TEMPORARY],
        Category::DNS_LOOP       => ['permanent' => true, 'bounce_type' => Type::HARD],
        Category::DNS_UNKNOWN    => ['permanent' => true, 'bounce_type' => Type::HARD],
        Category::FULL           => ['permanent' => false, 'bounce_type' => Type::SOFT],
        Category::INACTIVE       => ['permanent' => true, 'bounce_type' => Type::HARD],
        Category::LATIN_ONLY     => ['permanent' => false, 'bounce_type' => Type::SOFT],
        Category::OTHER          => ['permanent' => true, 'bounce_type' => Type::GENERIC],
        Category::OVERSIZE       => ['permanent' => false, 'bounce_type' => Type::SOFT],
        Category::OUTOFOFFICE    => ['permanent' => false, 'bounce_type' => Type::SOFT],
        Category::UNKNOWN        => ['permanent' => true, 'bounce_type' => Type::HARD],
        Category::UNRECOGNIZED   => ['permanent' => true, 'bounce_type' => Type::HARD],
        Category::USER_REJECT    => ['permanent' => true, 'bounce_type' => Type::HARD],
        Category::WARNING        => ['permanent' => false, 'bounce_type' => Type::SOFT],
    ];

    /**
     * @param $category
     *
     * @return CategoryObject
     *
     * @throws CategoryNotFound
     */
    public static function map($category)
    {
        if (!isset(static::$mappings[$category])) {
            throw new CategoryNotFound();
        }

        $mapping = static::$mappings[$category];

        return new CategoryObject($category, $mapping['bounce_type'], $mapping['permanent']);
    }
}
