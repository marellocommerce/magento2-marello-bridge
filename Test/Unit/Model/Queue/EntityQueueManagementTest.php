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
 * @copyright Copyright Marello (http://www.marello.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Marello\Bridge\Test\Unit\Model\Queue;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

use Marello\Bridge\Model\Queue\EntityQueueManagement;
use Marello\Bridge\Model\Queue\EntityQueueFactory;
use Marello\Bridge\Model\Queue\EntityQueueRepository;
use Marello\Bridge\Model\Processor\ProcessorRegistry;
use Marello\Bridge\Model\EntityRepositoryRegistry;

class EntityQueueManagementTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectManagerHelper $objectManagerHelper */
    protected $objectManagerHelper;

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
        $this->objectManagerHelper = new ObjectManagerHelper($this);

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
     * {@inheritdoc}
     */
    public function testGetSetBatchSize()
    {
        $defaultBatchSize = $this->queueManagement->getBatchSize();
        $this->assertEquals(50, $defaultBatchSize);

        $batchSize = $defaultBatchSize + 160;
        $this->queueManagement->setBatchSize($batchSize);

        $this->assertEquals($batchSize, $this->queueManagement->getBatchSize());
    }

    /**
     * {@inheritdoc}
     */
    public function testGetQueueCollection()
    {
        $this->setUpQueueCollectionSize();
        $this->setUpEntityQueueCollectionMock();
        $this->entityQueueCollectionMock
            ->expects($this->once())
            ->method('setPageSize')
            ->with($this->queueManagement->getBatchSize())
            ->willReturnSelf();

        $this->queueManagement->getEntityQueueCollection();
    }

    /**
     * Get collection of EntityQueue records
     * limited by the batch size and processed 0
     * loop through the collection
     * get config for single instance of EntityQueue
     * send it to the appropriate processor from config
     * if successful set processedAt && processed in separate array
     * after looping through, send it to the EntityQueueRepo to save all at once
     */
    public function testProcessingSingleQueuePage()
    {
        $this->setUpQueueCollectionSize();
        $this->setUpEntityQueueCollectionMock();
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

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->with($this->queueManagement->getBatchSize());

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('setCurPage');

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(2);

        $this->entityQueueMock
            ->expects($this->atLeastOnce())
            ->method('getEventType')
            ->willReturn('new_order');

        $this->processorsRegistryMock
            ->expects($this->any())
            ->method('getProcessors')
            ->willReturn(['new_order' => $processorMock]);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('getEntityData')
            ->willReturn(['entityAlias' => 'order', 'entityClass' => 'Order']);

        $this->entityRepositoryRegistryMock
            ->expects($this->any())
            ->method('getRegisteredRepositories')
            ->willReturn(['order' => $orderRepositoryMock]);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('getMagId')
            ->willReturn(1);

        $orderRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->with(1)
            ->willReturn($orderMock);

        $processorMock
            ->expects($this->any())
            ->method('process')
            ->with(['order' => $orderMock, 'type' => 'new_order'])
            ->willReturn(true);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('setProcessed')
            ->with(1);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('setProcessedAt')
            ->with(new \DateTime('now'));

        $this->entityQueueRepositoryMock
            ->expects($this->any())
            ->method('save')
            ->with($this->entityQueueMock)
            ->willReturn(true);

        $this->queueManagement->processQueue();
    }

    /**
     * 1 Loop through the collection and get config for single instance of EntityQueue
     * 2 Send it to the appropriate processor from config
     * 3 if unsuccessful do nothing with processed/processedAt
     * @throws \Exception
     */
    public function testProcessQueueFailed()
    {
        $this->setUpQueueCollectionSize();
        $this->setUpEntityQueueCollectionMock();

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->with($this->queueManagement->getBatchSize());

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('setCurPage');

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(1);

        $exceptionMessage = 'Test Exception';
        $this->entityQueueMock
            ->expects($this->any())
            ->method('getEventType')
            ->willThrowException(new \Exception($exceptionMessage));

        $this->processorsRegistryMock
            ->expects($this->never())
            ->method('getProcessors');

        $this->setExpectedException('\Exception', $exceptionMessage);
        $this->queueManagement->processQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessingMultiplePagesInQueue()
    {
        $this->setUpQueueCollectionSize(2);
        $this->setUpEntityQueueCollectionMock();
        $this->queueManagement->setBatchSize(1);

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

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->with($this->queueManagement->getBatchSize());

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('setCurPage');

        $this->entityQueueCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(2);

        $this->entityQueueMock
            ->expects($this->atLeastOnce())
            ->method('getEventType')
            ->willReturn('new_order');

        $this->processorsRegistryMock
            ->expects($this->any())
            ->method('getProcessors')
            ->willReturn(['new_order' => $processorMock]);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('getEntityData')
            ->willReturn(['entityAlias' => 'order', 'entityClass' => 'Order']);

        $this->entityRepositoryRegistryMock
            ->expects($this->any())
            ->method('getRegisteredRepositories')
            ->willReturn(['order' => $orderRepositoryMock]);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('getMagId')
            ->willReturn(1);

        $orderRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->with(1)
            ->willReturn($orderMock);

        $processorMock
            ->expects($this->any())
            ->method('process')
            ->with(['order' => $orderMock, 'type' => 'new_order'])
            ->willReturn(true);

        $this->entityQueueMock
            ->expects($this->exactly(2))
            ->method('setProcessed')
            ->with(1);

        $this->entityQueueMock
            ->expects($this->exactly(2))
            ->method('setProcessedAt')
            ->with(new \DateTime('now'));

        $this->entityQueueRepositoryMock
            ->expects($this->exactly(2))
            ->method('save')
            ->with($this->entityQueueMock)
            ->willReturn(true);

        $this->queueManagement->processQueue();

    }

    /**
     * {@inheritdoc}
     */
    private function setUpEntityQueueCollectionMock()
    {
        $this->entityQueueFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->entityQueueMock);

        $this->entityQueueMock
            ->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->entityQueueCollectionMock);

        $this->entityQueueCollectionMock
            ->expects($this->any())
            ->method('addFilter')
            ->with('processed', ['eq' => 0])
            ->willReturnSelf();
    }

    /**
     * {@inheritdoc}
     * @param int $size
     */
    private function setUpQueueCollectionSize($size = 1)
    {
        $entityQueueMocks = [$this->entityQueueMock];
        for ($i=1; $i < $size; $i++) {
            $entityQueueMocks[] = $this->entityQueueMock;
        }

        $this->entityQueueCollectionMock = $this->objectManagerHelper
            ->getCollectionMock('Marello\Bridge\Model\ResourceModel\EntityQueue\Collection',
                $entityQueueMocks
            );
    }
}
