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

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;

use Marello\Bridge\Model\Queue\EntityQueueFactory;
use Marello\Bridge\Model\Queue\EntityQueueRepository;
use Marello\Bridge\Api\EntityQueueRepositoryInterface;
use Marello\Bridge\Model\Queue\QueueEventTypeInterface;
use Marello\Bridge\Helper\Config;

class CancelOrder implements ObserverInterface
{
    /** @var EntityQueueFactory $entityQueueFactory */
    protected $entityQueueFactory;

    /** @var EntityQueueRepository $entityQueueRepository */
    protected $entityQueueRepository;

    /** @var Config $helper */
    protected $helper;

    /**
     * UpdateOrder constructor.
     * @param EntityQueueFactory                $entityQueueFactory
     * @param EntityQueueRepositoryInterface    $entityQueueRepository
     * @param Config                            $helper
     */
    public function __construct(
        EntityQueueFactory $entityQueueFactory,
        EntityQueueRepositoryInterface $entityQueueRepository,
        Config $helper
    ) {
        $this->entityQueueFactory       = $entityQueueFactory;
        $this->entityQueueRepository    = $entityQueueRepository;
        $this->helper                   = $helper;
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

        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order->isCanceled()) {
            return $this;
        }

        try {
            $result = $this->entityQueueRepository->findOneByIdAndEventType(
                $order->getId(),
                QueueEventTypeInterface::QUEUE_EVENT_TYPE_ORDER_CANCEL
            );

            if ($result) {
                return $this;
            }

            $queueEnitity = $this->entityQueueFactory->create();
            $queueEnitity->setMagId($order->getEntityId());
            $queueEnitity->setEventType(QueueEventTypeInterface::QUEUE_EVENT_TYPE_ORDER_CANCEL);
            $queueEnitity->setEntityData(['entityAlias' => 'order', 'entityClass' => get_class($order)]);
            $queueEnitity->setProcessed(0);
            $queueEnitity->setProcessedAt(null);
            $this->entityQueueRepository->save($queueEnitity);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
