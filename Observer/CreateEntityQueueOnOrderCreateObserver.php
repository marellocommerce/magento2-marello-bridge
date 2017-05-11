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
namespace Marello\Bridge\Observer;

use Psr\Log\LoggerInterface;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

use Marello\Bridge\Model\Queue\EntityQueueFactory;
use Marello\Bridge\Api\EntityQueueRepositoryInterface;
use Marello\Bridge\Model\Queue\QueueEventTypeInterface;
use Marello\Bridge\Helper\Config;

class CreateEntityQueueOnOrderCreateObserver implements ObserverInterface
{
    /** @var EntityQueueFactory $entityQueueFactory */
    protected $entityQueueFactory;

    /** @var EntityQueueRepositoryInterface $entityQueueRepository */
    protected $entityQueueRepository;

    /** @var Config $helper */
    protected $helper;

    /** @var LoggerInterface $logger */
    protected $logger;

    /**
     * {@inheritdoc}
     * @param EntityQueueFactory                $entityQueueFactory
     * @param EntityQueueRepositoryInterface    $entityQueueRepository
     * @param Config                            $helper
     * @param LoggerInterface                   $logger
     */
    public function __construct(
        EntityQueueFactory $entityQueueFactory,
        EntityQueueRepositoryInterface $entityQueueRepository,
        Config $helper,
        LoggerInterface $logger
    ) {
        $this->entityQueueFactory       = $entityQueueFactory;
        $this->entityQueueRepository    = $entityQueueRepository;
        $this->helper                   = $helper;
        $this->logger                   = $logger;
    }

    /**
     * @param Observer $observer
     * @return $this
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isBridgeEnabled()) {
            return $this;
        }
        
        $order = $observer->getEvent()->getOrder();
        if (!$this->isNewOrder($order)) {
            return $this;
        }

        try {
            $result = $this->entityQueueRepository->findOneByIdAndEventType(
                $order->getEntityId(),
                QueueEventTypeInterface::QUEUE_EVENT_TYPE_ORDER_CREATE
            );

            if ($result) {
                return $this;
            }

            $queueEnitity = $this->entityQueueFactory->create();
            $queueEnitity->setMagId($order->getEntityId());
            $queueEnitity->setEventType(QueueEventTypeInterface::QUEUE_EVENT_TYPE_ORDER_CREATE);
            $queueEnitity->setEntityData(['entityAlias' => 'order', 'entityClass' => get_class($order)]);
            $queueEnitity->setProcessed(0);
            $queueEnitity->setProcessedAt(null);
            $this->entityQueueRepository->save($queueEnitity);
        } catch (\Exception $e) {
            $this->logger->log('critical', $e->getMessage(), $e->getTrace());
        }

        return $this;
    }

    /**
     * Check if an order is new via state.
     * @param OrderInterface $order
     * @return bool
     */
    protected function isNewOrder(OrderInterface $order)
    {
        return (bool) ($order->getState() === Order::STATE_NEW);
    }
}
