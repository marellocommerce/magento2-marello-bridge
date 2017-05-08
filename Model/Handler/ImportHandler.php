<?php

/**
 * Marello
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is published at http://opensource.org/licenses/osl-3.0.php.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@marello.com so we can send you a copy immediately
 *
 * @category  Marello
 * @package   Bridge
 * @copyright Copyright 2016 Marello (http://www.marello.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Marello\Bridge\Model\Handler;

use Marello\Bridge\Api\ItemReaderInterface;
use Marello\Bridge\Model\Processor\ProcessorRegistry;
use Marello\Bridge\Model\Writer\EntityWriter;

class ImportHandler
{
    const DEFAULT_IMPORT = 'product';

    const BATCH_SIZE = 25;

    /** @var ItemReaderInterface $reader */
    protected $reader;

    /** @var $processor */
    protected $processor;

    /** @var ProcessorRegistry $processors */
    protected $processors;

    /** @var EntityWriter $writer */
    protected $writer;

    /**
     * {@inheritdoc}
     * @param ItemReaderInterface $reader
     * @param ProcessorRegistry $processorRegistry
     * @param EntityWriter $writer
     */
    public function __construct(
        ItemReaderInterface $reader,
        ProcessorRegistry $processorRegistry,
        EntityWriter $writer
    ) {
        $this->reader = $reader;
        $this->processors = $processorRegistry;
        $this->writer = $writer;
    }

    /**
     * Handle import
     * @throws \Exception
     */
    public function handleImport()
    {
        $itemsToWrite = [];
        $this->initialize();
        $stopExecution = false;

        while (!$stopExecution) {
            try {
                $readItems = $this->reader->read();

                if (null === $readItems) {
                    $stopExecution = true;
                    continue;
                }

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

            $processedItems = $this->process($readItems);
            if (null !== $processedItems) {
                $itemsToWrite = $processedItems;
                if (0 === (count($itemsToWrite) % self::BATCH_SIZE)) {
                    $this->write($itemsToWrite);
                    $itemsToWrite = [];
                }
            }
        }

        if (count($itemsToWrite) > 0) {
            $this->write($itemsToWrite);
        }
    }

    /**
     * {@inheritdoc}
     * @param string $processorAlias
     * @throws \InvalidArgumentException
     */
    protected function getProcessor($processorAlias = self::DEFAULT_IMPORT)
    {
        if (!is_null($this->processor)) {
            return $this->processor;
        }

        $processors = $this->processors->getProcessors();
        if (!isset($processors[$processorAlias]) && empty($processors[$processorAlias])) {
            throw new \InvalidArgumentException(sprintf('No processor found for alias "%s"', $processorAlias));
        }

        return $processors[$processorAlias];
    }

    /**
     * {@inheritdoc}
     * @param $items
     */
    protected function process($items)
    {
        try {
            return $this->processor->process($items);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     * @param $processedItems
     */
    protected function write($processedItems)
    {
        try {
            $this->writer->write($processedItems);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     * initialize processor
     */
    protected function initialize()
    {
        $this->processor = $this->getProcessor(self::DEFAULT_IMPORT);
    }
}
