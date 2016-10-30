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

use Marello\Bridge\Api\Data\ConnectorRegistryInterface;
use Marello\Bridge\Api\Data\DataConverterRegistryInterface;
use Marello\Bridge\Model\Transport\RestTransport;
use Marello\Bridge\Model\Queue\QueueEventTypeInterface;
use Marello\Bridge\Model\Converter\OrderDataConverter;

class UpdateOrderProcessor extends AbstractProcessor
{
    const TRANSITION_TYPE_INVOICE   = 'invoice';
    const TRANSITION_TYPE_PICKPACK  = 'prepare_shipping';
    const TRANSITION_TYPE_CANCELED  = 'cancel';

    /** @var OrderRepositoryInterface $orderRepository */
    protected $orderRepository;

    /**
     * UpdateOrderProcessor constructor.
     * @param ConnectorRegistryInterface $connectorRegistry
     * @param DataConverterRegistryInterface $converterRegistry
     * @param RestTransport $transport
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ConnectorRegistryInterface $connectorRegistry,
        DataConverterRegistryInterface $converterRegistry,
        RestTransport $transport,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository  = $orderRepository;

        parent::__construct($connectorRegistry, $converterRegistry, $transport);
    }

    /**
     * Process updated order data
     * @param array $orderData
     * @return $this
     * @throws \Exception
     */
    public function process(array $orderData)
    {
        $order = $orderData['order'];
        $jsonData = unserialize($order->getData('marello_data'));
        $data = json_decode($jsonData);

        if (!$data || !property_exists($data, 'id')) {
            return false;
        }
        
        // get all the order info
        $marelloOrderId = $data->id;
        $orderResult = $this->transport->fetchEntity(['id' => $marelloOrderId], '/orders');

        $decodedResult = json_decode($orderResult);
        $workFlowItemId = $decodedResult->workflowItem->id;
        $transition = $this->getWorkflowTransition($orderData['type']);
        $transitWorkflowResult = $this->transitOrder($workFlowItemId, $transition);

        if (strpos($transitWorkflowResult, 'The requested URL') === false && null !== $transitWorkflowResult) {
            $this->saveOrderData($orderResult, $order);
            if ($transition === self::TRANSITION_TYPE_CANCELED) {
                return true;
            }
        }

        if ($transition === self::TRANSITION_TYPE_INVOICE) {
            // update order with payment references etc..
            try {
                $this->updateOrderData($order, $marelloOrderId);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
            // order is invoiced, directly prepare for ship it.
            $transitWorkflowResult = $this->transitOrder($workFlowItemId, self::TRANSITION_TYPE_PICKPACK);
            if (strpos($transitWorkflowResult, 'The requested URL') === false && null !== $transitWorkflowResult) {
                $this->saveOrderData($orderResult, $order);
                return true;
            }
        }

        return false;
    }

    /**
     * @param $order
     * @param $marelloOrderId
     * @return mixed|null
     * @throws \Exception
     */
    protected function updateOrderData($order, $marelloOrderId)
    {
        $converter = $this->getDataConverterByAlias('order');
        $converter->setConversionType(OrderDataConverter::ORDER_CONVERSION_TYPE_UPDATE);
        $convertedOrder = $converter->convertEntity($order);
        // put request only handles certain attributes so get them from the converted order
        $orderData = [
            'billingAddress'    => $convertedOrder['billingAddress'],
            'shippingAddress'   => $convertedOrder['shippingAddress'],
            'paymentReference'  => $convertedOrder['paymentReference'],
            'invoicedAt'        => $convertedOrder['invoicedAt'],
            'invoiceReference'  => $convertedOrder['invoiceReference'],
        ];

        $this->setTransportConnector('default');
        $this->transport->getConnector()->setType('put');
        $this->transport->getConnector()->setMethod(sprintf('/orders/%s', $marelloOrderId));

        return $this->transport->synchronizeEntity($orderData);
    }

    /**
     * @param $workflowItemId
     * @param $transition
     * @return mixed|null|void
     * @throws \Exception
     */
    protected function transitOrder($workflowItemId, $transition)
    {
        $this->setTransportConnector('default');
        $this->transport->getConnector()->setType('get');
        $this->transport->getConnector()->setMethod('/workflow/transit');

        $item = [
            'workflowItemId' => sprintf('%s/%s', $workflowItemId, $transition)
        ];

        return $this->transport->synchronizeEntity($item);
    }

    /**
     * Set and save Marello data on order
     * @param $result
     * @param $order
     * @throws \Exception
     */
    protected function saveOrderData($result, $order)
    {
        $order->setData('marello_data', serialize($result));
        try {
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            throw new \Exception('Could not save the marello data on the order');
        }
    }

    /**
     * Get workflow transition based on type of update
     * @param $type
     * @return null|string
     */
    protected function getWorkflowTransition($type)
    {
        $transition = null;
        switch ($type) :
            case QueueEventTypeInterface::QUEUE_EVENT_TYPE_ORDER_INVOICE:
                $transition = self::TRANSITION_TYPE_INVOICE;
                break;
            case QueueEventTypeInterface::QUEUE_EVENT_TYPE_ORDER_CANCEL:
                $transition = self::TRANSITION_TYPE_CANCELED;
                break;
        endswitch;
        
        return $transition;
    }
}
