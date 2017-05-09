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
namespace Marello\Bridge\Test\Unit\Model\Queue;

use Marello\Bridge\Model\Queue\EntityQueueManagement;
use Marello\Bridge\Model\Queue\EntityQueueFactory;
use Marello\Bridge\Model\Queue\EntityQueueRepository;
use Marello\Bridge\Model\Processor\ProcessorRegistry;
use Marello\Bridge\Model\EntityRepositoryRegistry;

class EntityQueueManagementTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityQueueManagement $queueManagement */
    protected $queueManagement;
    
    /** @var  EntityQueueRepository $entityQueueRepositoryMock */
    protected $entityQueueRepositoryMock;

    /** @var EntityQueueFactory $entityQueueFactoryMock */
    protected $entityQueueFactoryMock;

    /** @var ProcessorRegistry $processorsRegistryMock */
    protected $processorsRegistryMock;

    /** @var EntityRepositoryRegistry $entityRepositoryRegistryMock */
    protected $entityRepositoryRegistryMock;

    protected $entityQueueCollectionMock;

    protected $entityQueueMock;

    /**
     * setup
     */
    public function setUp()
    {
        $this->entityQueueFactoryMock = $this->getMockBuilder(EntityQueueFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->processorsRegistryMock = $this->getMockBuilder(ProcessorRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProcessors'])
            ->getMock();
        
        $this->entityQueueRepositoryMock = $this->getMockBuilder(EntityQueueRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();

        $this->entityRepositoryRegistryMock = $this->getMockBuilder(EntityRepositoryRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRegisteredRepositories'])
            ->getMock();

        $this->entityQueueCollectionMock = $this->getMock(
            '\Marello\Bridge\Model\ResourceModel\EntityQueue\Collection',
            [],
            [],
            '',
            false
        );

        $this->entityQueueMock = $this->getMock(
            '\Marello\Bridge\Model\Queue\EntityQueue',
            [],
            [],
            'entityQueueMock',
            false
        );

        $this->queueManagement = new EntityQueueManagement(
            $this->entityQueueFactoryMock,
            $this->processorsRegistryMock,
            $this->entityQueueRepositoryMock,
            $this->entityRepositoryRegistryMock
        );
    }

    /**
     * @test
     *
     */
    public function testGetSetBatchSize()
    {
        $defaultBatchSize = $this->queueManagement->getBatchSize();
        $this->assertEquals(50, $defaultBatchSize);

        $batchSize = $defaultBatchSize + 160;
        $this->queueManagement->setBatchSize($batchSize);

        $this->assertEquals($batchSize, $this->queueManagement->getBatchSize());
    }

    public function testGetQueueCollection()
    {
        $this->setUpEntityQueueCollectionMock();
        $this->entityQueueCollectionMock
            ->expects($this->once())
            ->method('setPageSize')
            ->with($this->queueManagement->getBatchSize())
            ->willReturnSelf();

        $this->queueManagement->getEntityQueueCollection();
    }

    /**
     * get collection of EntityQueue records
     * limited by the batch size and processed 0
     * loop through the collection
     * get config for single instance of EntityQueue
     * send it to the appropriate processor from config
     * if successful set processedAt && processed in separate array
     * after looping through, send it to the EntityQueueRepo to save all at once
     */
    public function testProcessQueueSuccess()
    {
        $processorMock = $this->getMock(
            '\Marello\Bridge\Model\Processor\NewOrderProcessor',
            [],
            [],
            'processorMock',
            false
        );

        $orderRepositoryMock = $this->getMock(
            '\Magento\Sales\Model\OrderRepository',
            [],
            [],
            'orderRepositoryMock',
            false
        );

        $orderMock = $this->getMock(
            '\Magento\Sales\Model\Order',
            [],
            [],
            'orderMock',
            false
        );

        $this->setUpEntityQueueCollectionMock();
        $this->entityQueueCollectionMock
            ->expects($this->once())
            ->method('setPageSize')
            ->with($this->queueManagement->getBatchSize())
            ->willReturn([$this->entityQueueMock]);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('getEventType')
            ->willReturn('new_order');

        $this->processorsRegistryMock
            ->expects($this->once())
            ->method('getProcessors')
            ->willReturn(['new_order' => $processorMock]);

        $this->entityQueueMock
            ->expects($this->once())
            ->method('getEntityData')
            ->willReturn(['entityAlias' => 'order', 'entityClass' => 'Order']);

        $this->entityRepositoryRegistryMock
            ->expects($this->once())
            ->method('getRegisteredRepositories')
            ->willReturn(['order' => $orderRepositoryMock]);

        $this->entityQueueMock
            ->expects($this->once())
            ->method('getMagId')
            ->willReturn(1);

        $orderRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($orderMock);

        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with(['order' => $orderMock, 'type' => 'new_order'])
            ->willReturn(true);

        $this->entityQueueMock
            ->expects($this->once())
            ->method('setProcessed')
            ->with(1);

        $this->entityQueueMock
            ->expects($this->once())
            ->method('setProcessedAt')
            ->with(new \DateTime('now'));

        $this->entityQueueRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->entityQueueMock)
            ->willReturn(true);
        
        $this->queueManagement->processQueue();
    }

    /**
     * 1 Loop through the collection and get config for single instance of EntityQueue
     * 2 Send it to the appropriate processor from config
     * 3 if unsuccessful do nothing with processed/processedAt
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testProcessQueueFailed()
    {
        $processorMock = $this->getMock(
            '\Marello\Bridge\Model\Processor\NewOrderProcessor',
            [],
            [],
            'processorMock',
            false
        );

        $orderRepositoryMock = $this->getMock(
            '\Magento\Sales\Model\OrderRepository',
            [],
            [],
            'orderRepositoryMock',
            false
        );

        $orderMock = $this->getMock(
            '\Magento\Sales\Model\Order',
            [],
            [],
            'orderMock',
            false
        );
        $this->setUpEntityQueueCollectionMock();
        $this->entityQueueCollectionMock
            ->expects($this->once())
            ->method('setPageSize')
            ->with($this->queueManagement->getBatchSize())
            ->willReturn([$this->entityQueueMock]);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('getEventType')
            ->willReturn('new_order');

        $this->processorsRegistryMock
            ->expects($this->once())
            ->method('getProcessors')
            ->willReturn(['new_order' => $processorMock]);

        $this->entityQueueMock
            ->expects($this->once())
            ->method('getEntityData')
            ->willReturn(['entityAlias' => 'order', 'entityClass' => 'Order']);

        $this->entityRepositoryRegistryMock
            ->expects($this->once())
            ->method('getRegisteredRepositories')
            ->willReturn(['order' => $orderRepositoryMock]);

        $this->entityQueueMock
            ->expects($this->once())
            ->method('getMagId')
            ->willReturn(1);

        $orderRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($orderMock);
        
        $exceptionMessage = 'Could not process Queue item';
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with(['order' => $orderMock, 'type' => 'new_order'])
            ->willThrowException(new \Magento\Framework\Exception\LocalizedException(__($exceptionMessage)));

        $this->setExpectedException('\Magento\Framework\Exception\LocalizedException', $exceptionMessage);
        $this->queueManagement->processQueue();
    }

    private function setUpEntityQueueCollectionMock()
    {
        $this->entityQueueFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->entityQueueMock);

        $this->entityQueueMock
            ->expects($this->once())
            ->method('getCollection')
            ->willReturn($this->entityQueueCollectionMock);

        $this->entityQueueCollectionMock
            ->expects($this->once())
            ->method('addFilter')
            ->with('processed', ['eq' => 0])
            ->willReturnSelf();
    }
}
