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
namespace Marello\Bridge\Test\Unit\Handler;

use Marello\Bridge\Model\Handler\ImportHandler;
use Marello\Bridge\Api\ItemReaderInterface;
use Marello\Bridge\Model\Processor\ProcessorRegistry;
use Marello\Bridge\Model\Processor\ProductProcessor;
use Marello\Bridge\Model\Writer\EntityWriter;

class ImportHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImportHandler | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $importHandler;

    /** @var ProductProcessor $processorMock */
    protected $processorMock;

    /** @var ProcessorRegistry $processorRegistryMock */
    protected $processorRegistryMock;

    /** @var ItemReaderInterface $readerMock */
    protected $readerMock;

    /** @var EntityWriter $writerMock */
    protected $writerMock;

    protected function setUp()
    {
        $this->readerMock = $this->getMockBuilder(ItemReaderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processorMock = $this->getMockBuilder(ProductProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processorRegistryMock = $this->getMockBuilder(ProcessorRegistry::class)
            ->getMock();

        $this->writerMock = $this->getMockBuilder(EntityWriter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->importHandler = new ImportHandler(
            $this->readerMock,
            $this->processorRegistryMock,
            $this->writerMock
        );
    }

    /**
     * @test
     */
    public function testIfItemsAreReadProcessedAndWritten()
    {
        $this->readerMock->expects($this->at(0))
            ->method('read')
            ->willReturn([]);

        $this->readerMock->expects($this->at(1))
            ->method('read')
            ->willReturn(null);

        $this->processorRegistryMock->expects($this->exactly(1))
            ->method('getProcessors')
            ->willReturn(['product' => $this->processorMock]);

        $this->processorMock->expects($this->at(0))
            ->method('process')
            ->with([])
            ->willReturn(['processedItem']);

        $this->writerMock->expects($this->exactly(1))
            ->method('write')
            ->with(['processedItem']);

        $this->importHandler->handleImport();
    }

    /**
     * @test
     */
    public function testNoItemsToRead()
    {
        $this->readerMock->expects($this->at(0))
            ->method('read')
            ->willReturn(null);

        $this->readerMock->expects($this->exactly(1))
            ->method('read');

        $this->processorRegistryMock->expects($this->exactly(1))
            ->method('getProcessors')
            ->willReturn(['product' => $this->processorMock]);

        $this->processorMock->expects($this->never())
            ->method('process');

        $this->writerMock->expects($this->never())
            ->method('write');

        $this->importHandler->handleImport();
    }

    /**
     * @test
     */
    public function testNoItemsToProcess()
    {
        $this->readerMock->expects($this->at(0))
            ->method('read')
            ->willReturn([]);

        $this->readerMock->expects($this->at(1))
            ->method('read')
            ->willReturn(null);

        $this->processorRegistryMock->expects($this->exactly(1))
            ->method('getProcessors')
            ->willReturn(['product' => $this->processorMock]);

        $this->processorMock->expects($this->once())
            ->method('process')
            ->with([])
            ->willReturn(null);

        $this->writerMock->expects($this->never())
            ->method('write');

        $this->importHandler->handleImport();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No processor found for alias "product"
     *
     */
    public function noProcessorFoundForAliasInRegistry()
    {
        $this->processorRegistryMock->expects($this->exactly(1))
            ->method('getProcessors')
            ->willReturn([]);

        $this->readerMock->expects($this->never())
            ->method('read');

        $this->processorMock->expects($this->never())
            ->method('process');

        $this->writerMock->expects($this->never())
            ->method('write');

        $this->importHandler->handleImport();
    }
}
