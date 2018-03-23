<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Builder;

use Mautic\CoreBundle\Batches\Action\BatchAction;
use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Adapter\SourceAdapterInterface;

class BatchActionBuilder implements BatchActionBuilderInterface
{
    /**
     * @var SourceAdapterInterface
     */
    private $sourceAdapter;

    /**
     * @var HandlerAdapterInterface
     */
    private $handlerAdapter;

    /**
     * @see BatchActionBuilderInterface::setSourceAdapter()
     * {@inheritdoc}
     */
    public function setSourceAdapter(SourceAdapterInterface $sourceAdapter)
    {
        $this->sourceAdapter = $sourceAdapter;
        return $this;
    }

    /**
     * @see BatchActionBuilderInterface::setHandlerAdapter()
     * {@inheritdoc}
     */
    public function setHandlerAdapter(HandlerAdapterInterface $handlerAdapter)
    {
        $this->handlerAdapter = $handlerAdapter;
        return $this;
    }

    /**
     * @see BatchActionBuilderInterface::build()
     * {@inheritdoc}
     */
    public function build()
    {
        return new BatchAction($this->sourceAdapter, $this->handlerAdapter);
    }
}