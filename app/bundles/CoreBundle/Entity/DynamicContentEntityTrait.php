<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

trait DynamicContentEntityTrait
{
    /**
     * @var ArrayCollection
     */
    private $dynamicContent;

    /**
     * @param ClassMetadataBuilder $builder
     */
    static protected function addDynamicContentMetadata(ClassMetadataBuilder $builder)
    {
        $builder->createField('dynamicContent', 'array')
            ->columnName('dynamic_content')
            ->nullable()
            ->build();
    }

    /**
     * @return ArrayCollection
     */
    public function getDynamicContent()
    {
        return $this->dynamicContent;
    }

    /**
     * @param ArrayCollection $dynamicContent
     */
    public function setDynamicContent($dynamicContent)
    {
        $this->dynamicContent = $dynamicContent;
    }

    /**
     * @return array
     */
    public function getDynamicContentAsArray()
    {
        return $this->dynamicContent->toArray();
    }

    /**
     * Check dynamic content.
     */
    public function checkDynamicContent()
    {
        if (empty($this->dynamicContent) || ($this->dynamicContent instanceof ArrayCollection && empty($this->dynamicContent->toArray()))) {
            $this->resetDynamicContent();
        }
    }

    /**
     * Reset dynamic content.
     */
    public function resetDynamicContent()
    {
        $defaultDynamicContent = [
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
                                'filter'   => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->dynamicContent = new ArrayCollection($defaultDynamicContent);
    }
}