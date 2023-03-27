<?php

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Helper\DataExporterHelper;

class IteratorExportDataModel implements \Iterator
{
    private $position;
    private $model;
    private $args;
    private $callback;
    private $total;
    private $data;
    private $totalResult;
    private bool $skipOrdering;

    /**
     * @param AbstractCommonModel<T> $model
     * @param array<mixed>           $args
     * @template T of object
     */
    public function __construct(AbstractCommonModel $model, array $args, callable $callback, bool $skipOrdering = false)
    {
        $this->model        = $model;
        $this->args         = $args;
        $this->callback     = $callback;
        $this->position     = 0;
        $this->total        = 0;
        $this->totalResult  = 0;
        $this->data         = 0;
        $this->skipOrdering = $skipOrdering;
    }

    /**
     * Return the current element.
     *
     * @see http://php.net/manual/en/iterator.current.php
     *
     * @return mixed Can return any type
     *
     * @since 5.0.0
     */
    public function current()
    {
        return $this->data[$this->position];
    }

    /**
     * Move forward to next element.
     *
     * @see http://php.net/manual/en/iterator.next.php
     * @since 5.0.0
     */
    public function next(): void
    {
        ++$this->position;

        if ($this->position === $this->totalResult) {
            $this->getDataForExport();
        }
    }

    /**
     * Return the key of the current element.
     *
     * @see http://php.net/manual/en/iterator.key.php
     *
     * @return mixed scalar on success, or null on failure
     *
     * @since 5.0.0
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid.
     *
     * @see http://php.net/manual/en/iterator.valid.php
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     *              Returns true on success or false on failure
     *
     * @since 5.0.0
     */
    public function valid()
    {
        if ($this->position <= $this->totalResult && !is_null($this->data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @see http://php.net/manual/en/iterator.rewind.php
     * @since 5.0.0
     */
    public function rewind(): void
    {
        $this->getDataForExport();
    }

    private function getDataForExport(): void
    {
        $data       = new DataExporterHelper();
        $this->data = $data->getDataForExport(
            $this->total,
            $this->model,
            $this->args,
            $this->callback,
            $this->skipOrdering
        );
        $this->totalResult = $this->data ? count($this->data) : 0;
        $this->total += $this->totalResult;
        $this->position = 0;
    }
}
