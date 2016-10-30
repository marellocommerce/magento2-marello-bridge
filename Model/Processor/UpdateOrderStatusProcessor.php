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
namespace Marello\Bridge\Model\Processor;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

use Marello\Bridge\Api\Data\DataConverterRegistryInterface;
use Marello\Bridge\Api\Data\ConnectorRegistryInterface;
use Marello\Bridge\Model\Transport\RestTransport;
use Marello\Bridge\Model\Service\ShipmentService;

class UpdateOrderStatusProcessor extends AbstractProcessor
{
    const WORKFLOW_STEP_NAME_SHIPPED  = 'shipped';

    /** @var OrderRepositoryInterface $orderRepository */
    protected $orderRepository;

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    protected $searchCriteriaBuilder;
    
    /** @var ShipmentService $shipmentService */
    protected $shipmentService;

    /**
     * UpdateOrderProcessor constructor.
     * @param ConnectorRegistryInterface $connectorRegistry
     * @param DataConverterRegistryInterface $converterRegistry
     * @param RestTransport $transport
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentService $shipmentService
     */
    public function __construct(
        ConnectorRegistryInterface $connectorRegistry,
        DataConverterRegistryInterface $converterRegistry,
        RestTransport $transport,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentService $shipmentService
    ) {
        $this->orderRepository          = $orderRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->shipmentService          = $shipmentService;

        parent::__construct($connectorRegistry, $converterRegistry, $transport);
    }

    /**
     * Process updated order data
     * @return $this
     */
    public function process()
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('status', 'processing')
            ->addFilter('marello_data', null, 'neq')
            ->create();

        $orderResult = $this->orderRepository->getList($criteria);

        // no orders found to update
        if ($orderResult->getTotalCount() < 1) {
            return $this;
        }

        $orders = $orderResult->getItems();
        foreach ($orders as $order) {
            $jsonData = unserialize($order->getData('marello_data'));
            $marelloOrderData = json_decode($jsonData);

            // apparently it can be saved as NULL :/
            if (!$marelloOrderData) {
                continue;
            }

            if (!property_exists($marelloOrderData, 'id')) {
                continue;
            }

            // get all the order info from Marello
            $marelloOrderData = $this->transport->fetchEntity(['id' => $marelloOrderData->id], '/orders');
            if (strpos($marelloOrderData, 'The requested URL') !== false && null !== $marelloOrderData) {
                continue;
            }

            $marelloOrderData = json_decode($marelloOrderData);

            if ($marelloOrderData->workflowStep->name === self::WORKFLOW_STEP_NAME_SHIPPED) {
                // create shipment
                $this->shipmentService->createShipment($order);
            }
        }
    }
}
