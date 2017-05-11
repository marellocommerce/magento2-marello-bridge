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
namespace Marello\Bridge\Model\Processor;

use Magento\Sales\Api\OrderRepositoryInterface;

use Marello\Bridge\Api\Data\ConnectorRegistryInterface;
use Marello\Bridge\Api\Data\DataConverterRegistryInterface;
use Marello\Bridge\Api\MarelloTransportInterface;
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
     * @param MarelloTransportInterface $transport
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ConnectorRegistryInterface $connectorRegistry,
        DataConverterRegistryInterface $converterRegistry,
        MarelloTransportInterface $transport,
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
        $this->setTransportConnector('default');
        $this->transport->getConnector()->setType('get');
        $orderResult = $this->transport->call('/orders', ['id' => $marelloOrderId]);
        $fetchResult = $orderResult->getData('body');
        $decodedResult = json_decode($fetchResult);

        if (!$decodedResult || !property_exists($decodedResult, 'workflowItems')) {
            return false;
        }

        if (is_array($decodedResult->workflowItems)) {
            $workFlowItemId = $decodedResult->workflowItems[0]->id;
        }

        $transition = $this->getWorkflowTransition($orderData['type']);
        $transitWorkflowResult = $this->transitOrder($workFlowItemId, $transition);

        if (!$transitWorkflowResult->getData('error')) {
            $this->saveOrderData($orderResult->getData('body'), $order);
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
            if ($transitWorkflowResult->getData('error') && null !== $transitWorkflowResult) {
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
        return $this->transport->call(sprintf('/orders/%s', $marelloOrderId), $orderData);
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
        return $this->transport->call(sprintf('/workflow/transit/%s/%s', $workflowItemId, $transition));
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
