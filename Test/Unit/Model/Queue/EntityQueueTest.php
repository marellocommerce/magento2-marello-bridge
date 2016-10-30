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
namespace Marello\Bridge\Test\Unit\Model\Transport;

use Marello\Bridge\Model\Queue\EntityQueue;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class EntityQueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityQueue $entity */
    protected $entity;

    protected $objectManagerHelper;
    
    /**
     * setup
     */
    public function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->entity = $this->objectManagerHelper->getObject(EntityQueue::class, []);
    }

    public function tearDown()
    {
        unset($this->entity);
    }
    
    /**
     * @test
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     *
     */
    public function testGetSetAttributes($property, $value = null, $expected = null)
    {
        if ($value !== null) {
            call_user_func_array([$this->entity, 'set' . ucfirst($property)], [$value]);
        }

        $this->assertEquals($expected, call_user_func_array([$this->entity, 'get' . ucfirst($property)], []));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $magId          = 1234;
        $eventType      = 'new_order';
        $entityData     = ['id' => 4321];
        $createdAt      = new \DateTime('now');
        $processedAt    = new \DateTime('now');
        $processed      = 1;

        return [
            'magId'         => ['magId', $magId, $magId],
            'eventType'     => ['eventType', $eventType, $eventType],
            'entityData'    => ['entityData', $entityData, $entityData],
            'createdAt'     => ['createdAt', $createdAt, $createdAt],
            'processedAt'   => ['processedAt', $processedAt, $processedAt],
            'processed'     => ['processed', $processed, $processed],
        ];
    }
}
