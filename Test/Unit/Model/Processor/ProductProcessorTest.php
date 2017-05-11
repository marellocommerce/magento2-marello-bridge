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
namespace Marello\Bridge\Test\Unit\Processor;

use Marello\Bridge\Api\Data\DataConverterRegistryInterface;
use Marello\Bridge\Model\Converter\ProductDataConverter;
use Marello\Bridge\Model\Processor\ProductProcessor;
use Marello\Bridge\Api\StrategyInterface;

class ProductProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductProcessor $processor */
    protected $processor;

    /** @var ProductDataConverter $converterMock */
    protected $converterMock;

    /** @var DataConverterRegistryInterface $converterRegistryMock */
    protected $converterRegistryMock;

    /** @var StrategyInterface $stategyMock */
    protected $strategyMock;

    protected function setUp()
    {
        $this->converterRegistryMock = $this->getMockBuilder(DataConverterRegistryInterface::class)
            ->getMock();

        $this->strategyMock = $this->getMockBuilder(StrategyInterface::class)
            ->getMock();

        $this->converterMock = $this->getMockBuilder(ProductDataConverter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ProductProcessor(
            $this->strategyMock,
            $this->converterRegistryMock
        );
    }

    /**
     * @test
     */
    public function testIfItemsConvertedAndCheckedIfExistsThroughStrategy()
    {
        $items = [
            'product1' => [
                'name' => 'product1',
                'sku' => 'p1'
            ]
        ];

        $this->converterRegistryMock->expects($this->exactly(1))
            ->method('getDataConverters')
            ->willReturn(['product' => $this->converterMock]);

        $this->converterMock->expects($this->exactly(1))
            ->method('convertEntity')
            ->with($items['product1'])
            ->willReturn($items['product1']);

        $this->strategyMock->expects($this->exactly(1))
            ->method('process')
            ->with($items['product1'])
            ->willReturn($items['product1']);

        $result = $this->processor->process($items);
        $this->assertEquals($items['product1'], $result[0]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No converter found for alias "product"
     *
     */
    public function noConverterFoundForAliasInRegistry()
    {
        $this->converterRegistryMock->expects($this->exactly(1))
            ->method('getDataConverters')
            ->willReturn([]);

        $this->converterMock->expects($this->never())
            ->method('convertEntity');

        $this->strategyMock->expects($this->never())
            ->method('process');

        $this->processor->process(['item1']);
    }
}
