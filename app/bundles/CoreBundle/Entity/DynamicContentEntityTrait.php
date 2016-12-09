<?php

/*
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
     * Keep the default content set outside of $dynamicContent so that it can be used if $dynamicContent is emptied.
     *
     * @var array
     */
    private $defaultDynamicContent = [
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
     * @var array
     */
    private $dynamicContent = [];

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
        return (empty($this->dynamicContent)) ? $this->defaultDynamicContent : $this->dynamicContent;
    }

    /**
     * @param $dynamicContent
     *
     * @return $this
     */
    public function setDynamicContent($dynamicContent)
    {
        if (empty($dynamicContent)) {
            $dynamicContent = $this->defaultDynamicContent;
        }

        $this->dynamicContent = $dynamicContent;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultDynamicContent()
    {
        return $this->defaultDynamicContent;
    }
}
