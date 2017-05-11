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
 * @package   Marello
 * @copyright Copyright Marello (http://www.marello.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Marello\Bridge\Test\Integration;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

use Magento\Framework\Console\Cli;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\Framework\App\Cache\Manager;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;

use Marello\Bridge\Console\Command\ImportCommand;
use Marello\Bridge\Model\Writer\Attribute\DefaultAttributeWriter;
use Marello\Bridge\Test\Integration\Stub\TransportClientMock;
use Marello\Bridge\Api\TransportClientInterface;

/**
 * Class MarelloBridgeApiImportProductsTest
 * @package Marello\Bridge\Test\Integration
 */
class MarelloBridgeApiImportProductsTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\TestFramework\ObjectManager $objectManager */
    protected $objectManager;

    /** @var TransportClientMock $transportClientMock */
    protected $transportClientMock;

    /** @var ProductRepositoryInterface $productRepository */
    protected $productRepository;

    /** @var StockRegistryInterface $stockRegistry */
    protected $stockRegistry;

    /** @var StockItemRepositoryInterface $stockRepository */
    protected $stockRepository;

    /** @var SkuProcessor $skuProcessor */
    protected $skuProcessor;

    /** @var AdapterInterface $connection */
    protected $connection;

    /** @var Manager $cacheManager */
    protected $cacheManager;


    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        // Mock TransportClient for testing in 'full' with 3rd party api
        $this->objectManager->configure(
            ['preferences' => [TransportClientInterface::class => TransportClientMock::class]]
        );

        $this->transportClientMock = $this->objectManager->get(TransportClientMock::class);

        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->stockRegistry = $this->objectManager->create(StockRegistryInterface::class);
        $this->stockRepository = $this->objectManager->create(StockItemRepositoryInterface::class);
        $this->skuProcessor = $this->objectManager->get(SkuProcessor::class);

        $resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $resourceConnection->getConnection();

        $this->cacheManager = $this->objectManager->get(Manager::class);
    }

    /**
     * @test
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadConfigAndProductFixture
     * @dataProvider getExistingProductResponse
     * 1) Get Magento's Cli
     * 2) Verify that the application has picked up ImportCommand
     * 3) Setup Input & Output for command to run
     * 4) Run command
     * 5) Verify that command ran succesfully
     */
    public function importExistingFullProductEndToEnd($existingProductResponse)
    {
        // set response for current test
        $this->transportClientMock->setDummyResponseData($existingProductResponse);

        $product = $this->productRepository->get('simple', false, null, true);
        $this->assertEquals('Simple Product', $product->getName());
        $this->assertEquals(10, $product->getPrice());

        $item = $this->stockRegistry->getStockItem($product->getId(), 1);
        $stockItem = $this->stockRepository->get($item->getItemId());
        $this->assertEquals(100, $stockItem->getQty(), 'Product Should start with a total stock of 100');

        /** @var Cli $cliApplication */
        $cliApplication = $this->objectManager->get(Cli::class);
        $this->assertTrue($cliApplication->has(ImportCommand::COMMAND_NAME));

        // when running in cli application add the ['command' => ApplicationStatusCommand::COMMAND_NAME] instead
        // of empty array
        $input = new ArrayInput([]);
        $output = new StreamOutput(fopen('php://memory', 'w', false));

        $command = $this->objectManager->get(ImportCommand::class);
        $result = $command->run($input, $output);

        $this->assertNotEmpty($this->transportClientMock->getMessage('isMarelloApiAvailable'));
        $this->assertContains(
            'Marello API Is Available',
            $this->transportClientMock->getMessage('isMarelloApiAvailable')
        );

        $this->assertContains('Marello API Get Call', $this->transportClientMock->getMessage('restGetCall'));

        // assert that product data is in fact updated.
        $product = $this->productRepository->get('simple', false, null, true);

        // checking simple attributes
        $this->assertEquals('Wauwie!', $product->getName());
        $this->assertEquals(Visibility::VISIBILITY_BOTH, $product->getVisibility());
        $this->assertEquals(DefaultAttributeWriter::DEFAULT_TAX_CLASS_ID, $product->getTaxClassId());
        $this->assertEquals(Status::STATUS_ENABLED, $product->getStatus());

        // check prices
        $this->assertEquals(56, $product->getPrice());

        // check websites
        $websiteIds = $product->getWebsiteIds();
        $this->assertEquals([0 => 1, 0 => '0'], $websiteIds);
        $this->assertEquals(ImportCommand::RETURN_SUCCESS, $result);

        $this->resetTransportClient();
    }

    /**
     * @test
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadConfigAndProductFixture
     * @dataProvider getNewProductResponse
     * 1) Get Magento's Cli
     * 2) Verify that the application has picked up ImportCommand
     * 3) Setup Input & Output for command to run
     * 4) Run command
     * 5) Verify that command ran succesfully
     */
    public function importNewProductsEndToEnd($newProductResponse)
    {
        // set response for current test
        $this->transportClientMock->setDummyResponseData($newProductResponse);

        // need to use skuProcessor in order to verify the product doesn't exist
        // product repository will throw an exception and will not continue with tests...
        $products = $this->skuProcessor->getOldSkus();
        $this->assertArrayNotHasKey('newsimple', $products);

        /** @var Cli $cliApplication */
        $cliApplication = $this->objectManager->get(Cli::class);
        $this->assertTrue($cliApplication->has(ImportCommand::COMMAND_NAME));

        // when running in cli application add the ['command' => ApplicationStatusCommand::COMMAND_NAME] instead
        // of empty array
        $input = new ArrayInput([]);
        $output = new StreamOutput(fopen('php://memory', 'w', false));

        $command = $this->objectManager->get(ImportCommand::class);
        $result = $command->run($input, $output);

        $this->cleanCache();

        $this->assertNotEmpty($this->transportClientMock->getMessage('isMarelloApiAvailable'));
        $this->assertContains(
            'Marello API Is Available',
            $this->transportClientMock->getMessage('isMarelloApiAvailable')
        );
        $this->assertContains('Marello API Get Call', $this->transportClientMock->getMessage('restGetCall'));

        // assert that product data is in fact created.
        $product = $this->productRepository->get('newsimple', false, null, true);
        $this->assertEquals(DefaultAttributeWriter::DEFAULT_TAX_CLASS_ID, $product->getTaxClassId());
        $this->assertEquals('New Simple product', $product->getName());
        $this->assertEquals(Visibility::VISIBILITY_BOTH, $product->getVisibility());

        // check prices
        $this->assertEquals(56, $product->getPrice());

        // check websites
        $websiteIds = $product->getWebsiteIds();
        $this->assertEquals([0 => 1, 0 => '0'], $websiteIds);
        $this->assertEquals(ImportCommand::RETURN_SUCCESS, $result);

        $this->resetTransportClient();
    }

    /**
     * reset the transport client mock class
     */
    protected function resetTransportClient()
    {
        // clear 'total calls' to the API
        $this->transportClientMock->resetTotalCalls();

        // reset the all received messages
        $this->transportClientMock->removeAllMessages();

        // reset the dummy response
        $this->transportClientMock->clearDummyResponseData();

        // just checking that all data is actually cleared
        $this->assertEmpty($this->transportClientMock->getMessages());
        $this->assertNull($this->transportClientMock->getDummyResponse());
        $this->assertEquals(0, $this->transportClientMock->getTotalCalls());
    }


    /**
     * Get a dummy response for an existing product
     * @return array
     */
    public function getExistingProductResponse()
    {
        return [
            ['Dummy Response for existing products' => '[{"id":1,"name":"Wauwie!","sku":"simple","status":{"name":"enabled","label":"Enabled"},"organization":{"id":1},"createdAt":"2017-02-13T07:41:57+00:00","updatedAt":"2017-02-13T07:41:58+00:00","prices":[{"currency":"EUR","value":"50.0000"},{"currency":"USD","value":"56.0000"}],"channelPrices":[{"currency":"USD","value":"45.0000","channel":{"id":3,"code":"pos_nyc"}},{"currency":"USD","value":"45.5000","channel":{"id":4,"code":"pos_washington"}}],"channels":[{"id":3,"name":"Flagship Store New York","code":"pos_nyc","active":true,"channelType":"pos"},{"id":4,"name":"Store Washington D.C.","code":"pos_washington","active":true,"channelType":"pos"},{"id":5,"name":"HQ","code":"marello_headquarters","active":true,"channelType":"marello"}],"inventoryItems":[{"currentLevel":{"inventory":150,"allocatedInventory":0},"warehouse":{"id":1}}]}]'],
        ];
    }

    /**
     * Get a dummy response for a new product
     * @return array
     */
    public function getNewProductResponse()
    {
        return [
            ['Dummy Response for new products' => '[{"id":2,"name":"New Simple product","sku":"newsimple","status":{"name":"enabled","label":"Enabled"},"organization":{"id":1},"createdAt":"2017-02-13T07:41:57+00:00","updatedAt":"2017-02-13T07:41:58+00:00","prices":[{"currency":"EUR","value":"50.0000"},{"currency":"USD","value":"56.0000"}],"channelPrices":[{"currency":"USD","value":"45.0000","channel":{"id":3,"code":"pos_nyc"}},{"currency":"USD","value":"45.5000","channel":{"id":4,"code":"pos_washington"}}],"channels":[{"id":3,"name":"Flagship Store New York","code":"pos_nyc","active":true,"channelType":"pos"},{"id":4,"name":"Store Washington D.C.","code":"pos_washington","active":true,"channelType":"pos"},{"id":5,"name":"HQ","code":"marello_headquarters","active":true,"channelType":"marello"}],"inventoryItems":[{"currentLevel":{"inventory":150,"allocatedInventory":0},"warehouse":{"id":1}}]}]']
        ];
    }

    /**
     * 'Workaround' for loading fixtures from files
     * which are not in the Magento testsuite directory
     */
    public static function loadProductFixture()
    {
        include __DIR__ . '/_files/simple_product.php';
    }

    /**
     * {@inheritdoc]
     */
    public static function loadConfigFixture()
    {
        include __DIR__ . '/_files/module-config.php';
    }

    /**
     * {@inheritdoc}
     */
    public static function loadConfigAndProductFixture()
    {
        self::loadConfigFixture();
        self::loadProductFixture();
    }

    /**
     * clean caches
     */
    public function cleanCache()
    {
        $cacheTypes = $this->cacheManager->getAvailableTypes();
        $this->cacheManager->clean($cacheTypes);
    }
}
