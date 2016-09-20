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
     * @param ClassMetadata $builder
     */
    static protected function addDynamicContentMetadata(ClassMetadataBuilder $builder)
    {
        $builder->createField('dynamicContent', 'array')
            ->columnName('dynamic_content')
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
        return (array) $this->dynamicContent;
    }

    /**
     * @param string $token          The dynamic content token
     * @param string $defaultContent The default content to use in case no filters match.
     * @param array  $filters        The dynamic content filters.
     *
     * @return $this
     */
    public function addDynamicContent($token, $defaultContent, $filters)
    {
        $this->dynamicContent->set($token, ['content' => $defaultContent, 'filters' => $filters]);

        return $this;
    }

    /**
     * @param string $token The dynamic content token.
     *
     * @return array An array containing the default content and the filters.
     */
    public function getDynamicContentForToken($token)
    {
        if ($this->dynamicContent->containsKey($token)) {
            return $this->dynamicContent->get($token);
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
                                'glue'   => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->dynamicContent = new ArrayCollection($defaultDynamicContent);
    }
}