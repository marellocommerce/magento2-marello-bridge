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

namespace Marello\Bridge\Test\Unit\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;

use Marello\Bridge\Observer\CreateEntityQueueOnOrderCreateObserver;
use Marello\Bridge\Model\Queue\EntityQueueFactory;
use Marello\Bridge\Api\EntityQueueRepositoryInterface;
use Marello\Bridge\Model\Queue\QueueEventTypeInterface;
use Marello\Bridge\Helper\Config;

class AddEntityQueueOnOrderCreationObserverTest extends \PHPUnit_Framework_TestCase
{
    /** @var Observer $eventObserverMock */
    protected $eventObserverMock;
    protected $entityQueueFactoryMock;
    protected $entityQueueRepositoryMock;
    protected $configurationHelperMock;

    protected $unit;

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


        $this->unit = new CreateEntityQueueOnOrderCreateObserver(
            $this->entityQueueFactoryMock,
            $this->entityQueueRepositoryMock,
            $this->configurationHelperMock
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

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $eventMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getState')
            ->willReturn(Order::STATE_NEW);



        $this->unit->execute($this->eventObserverMock);
    }
}
