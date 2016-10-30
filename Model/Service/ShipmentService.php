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
namespace Marello\Bridge\Model\Service;

use Psr\Log\LoggerInterface;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Convert\Order as OrderConverter;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Grid;

use Marello\Bridge\Model\OrderGridSyncRefresh;

class ShipmentService
{
    /** @var OrderConverter $orderConverter */
    protected $orderConverter;

    /** @var LoggerInterface $logger */
    protected $logger;

    /** @var Transaction $dbTransaction */
    protected $dbTransaction;

    /** @var Grid $orderGrid */
    protected $orderGrid;

    public function __construct(
        OrderConverter $orderConverter,
        Transaction $dbTransaction,
        OrderGridSyncRefresh $orderGrid
    ) {
        $this->orderConverter       = $orderConverter;
        $this->dbTransaction        = $dbTransaction;
        $this->orderGrid            = $orderGrid;
    }

    /**
     * Create shipment for order
     * @param Order $order
     * @throws \Exception
     * @throws LocalizedException
     */
    public function createShipment(Order $order)
    {
        // Check if order can be shipped or has already shipped
        if (! $order->canShip()) {
            throw new LocalizedException(
                __('You can\'t create an shipment.')
            );
        }
        $shipment = $this->orderConverter->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            // Check if order item has qty to ship or is virtual
            if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();

            // Create shipment item with qty
            $shipmentItem = $this->orderConverter->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            // Add shipment item to shipment
            $shipment->addItem($shipmentItem);
        }

        $shipment->register();
        try {
            // Save created shipment and order
            $this->saveShipment($shipment);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Save shipment and order in one transaction
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return $this
     */
    protected function saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transaction = $this->dbTransaction;
        $transaction->addObject($shipment);
        $transaction->addObject($shipment->getOrder());
        $transaction->save();
        $this->orderGrid->refresh($shipment->getOrder());

        return $this;
    }
}
