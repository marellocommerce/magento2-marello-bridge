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

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel;

use Marello\Bridge\Helper\EntityIdentifierHelper;
use Marello\Bridge\Model\Writer\Attribute\WebsiteAttributeWriter;

class WebsiteAttributeWriterTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteAttributeWriter $websiteAttributeWriter */
    protected $websiteAttributeWriter;

    /** @var AdapterInterface $connection */
    protected $connection;

    public function setUp()
    {
        $resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->connection = $this->getMockBuilder(AdapterInterface::class)
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
            ->setMethods(['getProductWebsiteTable'])
            ->getMock();

        $resourceConnection->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $resourceFactory->expects($this->any())
            ->method('create')
            ->willReturn($resourceModelMock);

        $helper->expects($this->exactly(1))
            ->method('getEntityIdentifier');

        $resourceModelMock->expects($this->any())
            ->method('getProductWebsiteTable')
            ->willReturn('catalog_product_website');

        $this->websiteAttributeWriter = new WebsiteAttributeWriter($resourceConnection, $resourceFactory, $helper);
    }


    /**
     * @test
     */
    public function productHasNotBeenLinkedToWebsiteBecauseWebsitesIsEmpty()
    {
        $item = [
            'entity_id' => 1
        ];

        $this->connection->expects($this->never())
            ->method('quoteInto');

        $this->connection->expects($this->never())
            ->method('insertOnDuplicate');

        $this->websiteAttributeWriter->prepareAndSaveAttributeData($item);
    }

    /**
     * @test
     */
    public function productHasBeenLinkedToWebsite()
    {
        $item = [
            'entity_id' => 1,
            'name' => 'simple product',
            'status' => 'enabled',
            'tax_class_id' => 2,
            'visibility'    => 4,
            'websites'  => [
                0 => 1,
                1 => 5
            ]
        ];

        $this->connection->expects($this->exactly(2))
            ->method('quoteInto');

        $this->connection->expects($this->exactly(1))
            ->method('insertOnDuplicate')
            ->with(
                'catalog_product_website',
                [
                    [
                        'product_id' => $item['entity_id'],
                        'website_id' => $item['websites'][0]
                    ],
                    [
                        'product_id' => $item['entity_id'],
                        'website_id' => $item['websites'][1]
                    ],
                ]
            );

        $this->websiteAttributeWriter->prepareAndSaveAttributeData($item);
    }
}
