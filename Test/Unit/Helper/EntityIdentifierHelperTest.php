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
namespace Marello\Bridge\Test\Unit\Helper;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;

use Marello\Bridge\Helper\EntityIdentifierHelper;

class EntityIdentifierHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function testMagentoVersionCompareCheckForEntityIdentifier()
    {
        $mockMetaDataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMetaData = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMetaDataPool->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($mockMetaData);

        $mockMetaData->expects($this->atLeastOnce())
            ->method('getIdentifierField')
            ->willReturn('entity_id');

        $helper = new EntityIdentifierHelper($mockMetaDataPool);

        $this->assertEquals('entity_id', $helper->getEntityIdentifier());
    }
}
