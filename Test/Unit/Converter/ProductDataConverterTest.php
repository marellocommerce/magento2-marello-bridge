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
namespace Marello\Bridge\Test\Unit\Model\Converter;

use Marello\Bridge\Model\Converter\ProductDataConverter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Marello\Bridge\Helper\Config;

class ProductDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectManager $objectManager */
    protected $objectManager;

    /** @var ProductDataConverter $productDataConverter */
    protected $productDataConverter;

    /** @var Config|PHPUnit_Framework_MockObject  */
    protected $helperMock;

    /**
     * setup
     */
    public function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->helperMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productDataConverter = new ProductDataConverter($this->helperMock);
    }

    /**
     * Call protected methods for testing
     * @param $obj
     * @param $name
     * @param array $args
     * @return mixed
     */
    protected static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    protected static function getProtectedOrPrivateProperty($obj, $propertyName)
    {
        $class = new \ReflectionClass($obj);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * @test
     */
    public function testIfProductIsCorrectlyConvertedForMagento()
    {
        $jsonResponse = '{"id":1,"name":"Wauwie!","sku":"simple","status":{"name":"enabled","label":"Enabled"},"organization":{"id":1},"createdAt":"2017-02-13T07:41:57+00:00","updatedAt":"2017-02-13T07:41:58+00:00","prices":[{"currency":"USD","value":"56.0000"}],"channelPrices":[{"currency":"USD","value":"45.0000","channel":{"id":3,"code":"pos_nyc"}},{"currency":"USD","value":"45.5000","channel":{"id":4,"code":"pos_washington"}}],"channels":[{"id":3,"name":"Flagship Store New York","code":"pos_nyc","active":true,"channelType":"pos"},{"id":4,"name":"Store Washington D.C.","code":"pos_washington","active":true,"channelType":"pos"},{"id":5,"name":"HQ","code":"marello_headquarters","active":true,"channelType":"marello"}],"inventoryItems":[{"currentLevel":{"inventory":150,"allocatedInventory":0},"warehouse":{"id":1}}]}';
        $entity = json_decode($jsonResponse);

        $this->helperMock
            ->expects($this->atLeastOnce())
            ->method('getWebsitesByCurrency')
            ->willReturn([0]);

        $this->helperMock
            ->expects($this->atLeastOnce())
            ->method('getDefaultCurrency')
            ->willReturn('USD');

        $result = $this->productDataConverter->convertEntity($entity);
        $this->assertNotEmpty($result);
        $expectedResult = $this->getExpectedResult();
        $this->assertEquals($result, $expectedResult);

        $this->assertArrayHasKey('sku', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('websites', $result);
        $this->assertArrayHasKey('stores', $result);
        $this->assertArrayHasKey('prices', $result);
        $this->assertArrayHasKey('visibility', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('qty', $result);
    }

    protected function getExpectedResult()
    {
        return [
            'sku' => 'simple',
            'name' => 'Wauwie!',
            'websites' => [
                0 => null,
                1 => null,
                2 => null
            ],
            'stores' => [
                "" => null,
                0 => [
                    0 => 0
                ]
            ],
            'prices' => [
                0 => [
                    'websites' => [
                        3 => 0
                    ],
                    'price' => 56.00
                ]
            ],
            'visibility' => 4,
            'status'    => 1,
            'qty'       => 150
        ];
    }
}
