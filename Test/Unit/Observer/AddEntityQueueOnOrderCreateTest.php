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

namespace Marello\Bridge\Test\Unit\Observer;

use Psr\Log\LoggerInterface;

use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;

use Marello\Bridge\Observer\CreateEntityQueueOnOrderCreate;
use Marello\Bridge\Model\Queue\EntityQueueFactory;
use Marello\Bridge\Api\EntityQueueRepositoryInterface;
use Marello\Bridge\Api\Data\EntityQueueInterface;
use Marello\Bridge\Helper\Config;

class AddEntityQueueOnOrderCreateTest extends \PHPUnit_Framework_TestCase
{
    /** @var Observer $eventObserverMock */
    protected $eventObserverMock;

    /** @var EntityQueueFactory $entityQueueFactoryMock */
    protected $entityQueueFactoryMock;

    /** @var EntityQueueRepositoryInterface $entityQueueRepositoryMock */
    protected $entityQueueRepositoryMock;

    /** @var Config $configurationHelperMock */
    protected $configurationHelperMock;

    /** @var CreateEntityQueueOnOrderCreate $unit */
    protected $unit;

    /** @var LoggerInterface $loggerMock */
    protected $loggerMock;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->eventObserverMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getObject',
                    'getDataObject',
                    'getEvent'
                ]
            )
            ->getMock();

        $this->entityQueueFactoryMock = $this->getMockBuilder(EntityQueueFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityQueueRepositoryMock = $this->getMockBuilder(EntityQueueRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationHelperMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->unit = new CreateEntityQueueOnOrderCreate(
            $this->entityQueueFactoryMock,
            $this->entityQueueRepositoryMock,
            $this->configurationHelperMock,
            $this->loggerMock
        );
    }

    /**
     * @test
     */
    public function testCreatingEntityQueueRecordOnOrderCreation()
    {
        $this->configurationHelperMock->expects($this->once())
            ->method('isBridgeEnabled')
            ->willReturn(true);

        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $entityQueueMock = $this->getMockBuilder(EntityQueueInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $eventMock
            ->method('getData')
            ->with('order')
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getState')
            ->willReturn(Order::STATE_NEW);

        $this->entityQueueFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($entityQueueMock);

        $this->entityQueueRepositoryMock
            ->expects($this->once())
            ->method('save');

        $this->unit->execute($this->eventObserverMock);
    }
}
