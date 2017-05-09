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
namespace Marello\Bridge\Model\Converter;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

use Marello\Bridge\Api\Data\DataConverterInterface;
use Marello\Bridge\Helper\Config;

class OrderDataConverter implements DataConverterInterface
{
    const ORDER_CONVERSION_TYPE_NEW     = 'order_create';
    const ORDER_CONVERSION_TYPE_UPDATE  = 'order_update';

    /** @var Config $helper */
    protected $helper;

    /** @var string $conversionType */
    protected $conversionType;

    /**
     * OrderDataConverter constructor.
     * @param Config $helper
     */
    public function __construct(Config $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Prepare a Magento order to fit the data structure
     * of Marello Order
     * @param $order
     * @return array
     */
    public function convertEntity($order)
    {
        $reference = $order->getIncrementId();
        $websiteId = $order->getStore()->getWebsite()->getId();
        $salesChannel = $this->helper->getChannelCode($websiteId);

        if ($this->helper->isTestModeEnabled()) {
            $reference = $reference . 'TEST';
        }
        
        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
        $paymentDetails  = $this->getPaymentDetails($order->getPayment());

        $shippingMethod = $order->getShippingDescription();
        $shippingAmount = $order->getShippingAmount();

        // basic order data
        // customer is added at a later stage
        $data = [
            'orderReference'  => $reference,
            'salesChannel'    => $salesChannel,
            'currency'        => 'EUR',//$order->getOrderCurrencyCode(),
            'subtotal'        => $this->formatAmount($order->getSubtotal()),
            'totalTax'        => $this->formatAmount($order->getTaxAmount()),
            'grandTotal'      => $this->formatAmount($order->getGrandTotal()),
            'discountAmount'  => $order->getDiscountAmount(),
            'couponCode'      => $order->getCouponCode(),
            'shippingMethod'  => $shippingMethod,
            'shippingAmountExclTax'  => $shippingAmount,
            'shippingAmountInclTax'  => $shippingAmount,
            'paymentMethod'   => $paymentMethod,
            'paymentDetails'  => $paymentDetails,
        ];

        // add addtional data such as payment reference, invoice date and invoice ref number
        if ($this->getConversionType() === self::ORDER_CONVERSION_TYPE_UPDATE) {
            $paymentReference = $this->getPaymentReference($order->getPayment());
            $data['paymentReference'] = $paymentReference;
            $invoices = $order->getInvoiceCollection();
            $invoiceReference = null;
            $invoicedAt = null;
            foreach ($invoices as $invoice) {
                $invoiceReference   = $invoice->getData('increment_id');
                $invoicedAt         = $invoice->getData('created_at');
            }

            $data['invoicedAt'] = $invoicedAt;
            $data['invoiceReference'] = $invoiceReference;
        }

        // prepare billing address
        $billingAddress = $order->getBillingAddress();
        $billingAddressData = $this->prepareAddressItem($billingAddress);
        $data = array_merge($data, $billingAddressData);

        // prepare shipping address
        $shippingAddress = $order->getShippingAddress();
        $shippingAddressData = $this->prepareAddressItem($shippingAddress);
        $data = array_merge($data, $shippingAddressData);

        // order items
        $lineItems = $order->getAllItems();
        $itemData = $this->prepareEntityLineItems($lineItems);
        $data = array_merge($data, $itemData);

        return $data;
    }

    /**
     * Prepare a Magento order line item to fit the data structure
     * of Marello OrderItem
     * @param $items
     * @return array
     */
    public function prepareEntityLineItems($items)
    {
        $itemData = [];
        foreach ($items as $_item) {
            if (is_null($_item->getParentItemId()) && $_item->getProductType() === Configurable::TYPE_CODE) {
                continue;
            }

            $data['product']        = $_item->getSku();
            $data['productName']    = $_item->getName();
            if ($_parentItem = $_item->getParentItem()) {
                $_item = $_parentItem;
            }

            $price              = $_item->getBasePrice();
            $orgPrice           = $_item->getBaseOriginalPrice();
            $purchasePriceIncl  = $_item->getPriceInclTax();
            $rowTotal           = ($_item->getRowTotalInclTax()) ? $_item->getRowTotalInclTax() : $_item->getRowTotal();

            $data['quantity']               = (int)$_item->getQtyOrdered();
            $data['originalPriceInclTax']   = $this->formatAmount($orgPrice);
            $data['originalPriceExclTax']   = $this->formatAmount($purchasePriceIncl);
            $data['purchasePriceIncl']      = $this->formatAmount($purchasePriceIncl);
            $data['price']                  = $this->formatAmount($price);
            $data['tax']                    = $this->formatAmount($_item->getTaxAmount());
            $data['taxPercent']             = ($_item->getTaxPercent() / 100);
            $data['rowTotalInclTax']        = $this->formatAmount($rowTotal);
            $data['rowTotalExclTax']        = $this->formatAmount($rowTotal);

            $itemData[] = $data;
        }

        return ['items' => $itemData];
    }

    /**
     * Prepare a Magento address to fit the data structure
     * of Marello Address
     * @param $address
     * @return mixed
     */
    public function prepareAddressItem($address)
    {
        $type = ($address->getData('address_type') === 'billing') ? 'billingAddress' : 'shippingAddress';

        $addressData[$type] = [
            'firstName'     => $address->getFirstname(),
            'lastName'      => $address->getLastname(),
            'country'       => $address->getCountryId(),
            'street'        => $address->getStreetLine(1),
            'street2'       => $address->getStreetLine(2),
            'city'          => $address->getCity(),
            'region'        => 'NL-NB',
            'postalCode'    => $address->getPostcode(),
            'phone'         => $address->getTelephone(),
            'company'       => $address->getCompany()
        ];

        return $addressData;
    }
    
    /**
     * Convert amounts to float and round them on 4 decimals
     *
     * @param $amount
     * @return float
     */
    public function formatAmount($amount)
    {
        return round((float)$amount, 4);
    }

    /**
     * Get payment details from payment instance
     * @param $payment
     * @return string
     */
    public function getPaymentDetails($payment)
    {
        $method = $payment->getMethodInstance();
        $paymentMethodCode = $method->getCode();
        switch ($paymentMethodCode) :
            case 'ogone_basic':
            case 'ogone_banktransfernl':
            case 'ogone_cb':
            case 'ogone_ideal':
            case 'ops_iDeal':
                $details = ($method->getOgonePaymentBrand()) ? $method->getOgonePaymentBrand() : $method->getTitle();
                return $details;
                break;
            case 'checkmo':
                $details = $method->getTitle() . "\r\n";
                $details .= ($method->getData('additional_data')) ?
                    unserialize($method->getData('additional_data')) : $method->getMailingAddress();
                return $details;
                break;
            case 'adyen_hpp':
                $details = $method->getTitle() . "\r\n";
                $brandCode = null;
                $infoInstance = $method->getData('info_instance');
                $additionalInformation = $infoInstance->getData('additional_information');
                if ($additionalInformation && is_array($additionalInformation)) {
                    $brandCode = $additionalInformation['brand_code'];
                }
                $details .= $brandCode;
                return $details;
            default:
                return $method->getTitle();
                break;
        endswitch;
    }

    /**
     * Get payment reference if applicable
     * @param $payment
     * @return null|string
     */
    private function getPaymentReference($payment)
    {
        $method = $payment->getMethodInstance();
        $paymentMethodCode = $method->getCode();
        switch ($paymentMethodCode) :
            case 'ogone_basic':
            case 'ogone_banktransfernl':
            case 'ogone_cb':
            case 'ogone_ideal':
            case 'ops_iDeal':
                return 'N/A';
                break;
            case 'adyen_hpp':
                return $payment->getData('adyen_psp_reference');
            default:
                return 'N/A';
                break;
        endswitch;
    }

    /**
     * Get conversion type of data
     */
    public function getConversionType()
    {
        return $this->conversionType;
    }

    /**
     * Set conversion type of data
     * @param $type
     */
    public function setConversionType($type)
    {
        $this->conversionType = $type;
    }
}
