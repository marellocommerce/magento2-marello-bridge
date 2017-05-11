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
namespace Marello\Bridge\Test\Unit\Model\Writer\Attribute;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel;

use Marello\Bridge\Helper\EntityIdentifierHelper;
use Marello\Bridge\Model\Writer\Attribute\PriceAttributeWriter;

class PriceAttributeWriterTest extends \PHPUnit_Framework_TestCase
{
    /** @var PriceAttributeWriter $priceAttributeWriter */
    protected $priceAttributeWriter;

    public function setUp()
    {
        $resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $helper = $this->getMockBuilder(EntityIdentifierHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityIdentifier'])
            ->getMock();

        $resourceFactory = $this->getMockBuilder(ResourceModelFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resourceModelMock = $this->getMockBuilder(ResourceModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttribute'])
            ->getMock();

        $attributeMock = $this->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getBackendTable'])
            ->getMock();

        $resourceConnection->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $resourceFactory->expects($this->any())
            ->method('create')
            ->willReturn($resourceModelMock);

        $resourceModelMock->expects($this->atLeastOnce())
            ->method('getAttribute')
            ->with('price')
            ->willReturn($attributeMock);

        $helper->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn('entity_id');

        $attributeMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $attributeMock
            ->expects($this->once())
            ->method('getBackendTable')
            ->willReturn('price_table');

        $this->priceAttributeWriter = new PriceAttributeWriter($resourceConnection, $resourceFactory, $helper);
    }

    /**
     * @test
     */
    public function defaultPricesAreSavedOnly()
    {
        $item = [
            'entity_id' => 1,
            'prices' => [
                [
                    'websites' => [1 => 0],
                    'price' => 10
                ]
            ],
            'stores' => [
                0 => [0]
            ]
        ];

        $this->priceAttributeWriter->prepareAndSaveAttributeData($item);
    }

    /**
     * @test
     */
    public function defaultPricesAndChannelPricesAreSaved()
    {
        $item = [
            'entity_id' => 1,
            'prices' => [
                [
                    'websites' => [1 => 0],
                    'price' => 10
                ]
            ],
            'stores' => [
                0 => [0]
            ],
            'website_prices' => [0 => 0]
        ];

        $this->priceAttributeWriter->prepareAndSaveAttributeData($item);
    }
}
