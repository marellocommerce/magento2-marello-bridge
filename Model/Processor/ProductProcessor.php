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

use Marello\Bridge\Api\Data\DataConverterRegistryInterface;
use Marello\Bridge\Api\Data\ConnectorRegistryInterface;
use Marello\Bridge\Model\Transport\RestTransport;
use Marello\Bridge\Model\Writer\EntityWriter;

class ProductProcessor extends AbstractProcessor
{
    /** @var EntityWriter $writer */
    protected $writer;

    /**
     * ProductProcessor constructor.
     * @param ConnectorRegistryInterface $connectorRegistry
     * @param DataConverterRegistryInterface $converterRegistry
     * @param RestTransport $transport
     * @param EntityWriter $writer
     */
    public function __construct(
        ConnectorRegistryInterface $connectorRegistry,
        DataConverterRegistryInterface $converterRegistry,
        RestTransport $transport,
        EntityWriter $writer
    ) {
        $this->writer = $writer;
        parent::__construct($connectorRegistry, $converterRegistry, $transport);
    }

    /**
     * Process products for import
     * @return $this
     */
    public function process()
    {
        $products = $this->read();
        foreach ($products as $entity) {
            $processedItems[] = $this->getDataConverterByAlias('product')->convertEntity($entity);
        }

        try {
            $this->writer->write($processedItems);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
    
    /**
     * @return array|mixed
     */
    public function read()
    {
        $page = 1;
        $result = true;
        $products = [];
        while ($result) {
            $connector = $this->getConnectorByAlias('default', 'import');
            $connector->setMethod('/products');
            $this->transport->setConnector($connector);
            $fetchResult = $this->transport->fetchEntity(['page' => $page], '/products');
            $results = json_decode($fetchResult);
            if (empty($results)) {
                $results = [];
                $result = false;
            }
            $products = array_merge($products, $results);
            $page++;
        }
        
        return $products;
    }
}
