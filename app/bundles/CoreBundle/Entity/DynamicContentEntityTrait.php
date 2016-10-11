<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Entity;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

trait DynamicContentEntityTrait
{
    /**
     * @var array
     */
    private $dynamicContent = [
        [
            'tokenName' => null,
            'content'   => null,
            'filters'   => [
                [
                    'content' => null,
                    'filters' => [
                        [
                            'glue'     => null,
                            'field'    => null,
                            'object'   => null,
                            'type'     => null,
                            'operator' => null,
                            'display'  => null,
                            'filter'   => null,
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * @param ClassMetadataBuilder $builder
     */
    protected static function addDynamicContentMetadata(ClassMetadataBuilder $builder)
    {
        $builder->createField('dynamicContent', 'array')
            ->columnName('dynamic_content')
            ->nullable()
            ->build();
    }

    /**
     * @return array
     */
    public function getDynamicContent()
    {
        return $this->dynamicContent;
    }

    /**
     * @param array $dynamicContent
     */
    public function setDynamicContent($dynamicContent)
    {
        $this->dynamicContent = $dynamicContent;
    }
}
