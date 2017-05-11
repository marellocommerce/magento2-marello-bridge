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
namespace Marello\Bridge\Model\Queue;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Marello\Bridge\Api\Data\EntityQueueInterface;
use Marello\Bridge\Model\ResourceModel\EntityQueue as EntityQueueResourceModel;

class EntityQueue extends AbstractModel implements EntityQueueInterface, IdentityInterface
{
    /**
     * EntityQueue cache tag
     */
    const CACHE_TAG = 'marello_entity_queue';

    // @codingStandardsIgnoreStart
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(EntityQueueResourceModel::class);
    }
    // @codingStandardsIgnoreEnd

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return mixed
     */
    public function getMagId()
    {
        return $this->_getData(self::MAG_ID);
    }

    /**
     * @param $magId
     * @return $this
     */
    public function setMagId($magId)
    {
        return $this->setData(self::MAG_ID, $magId);
    }

    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->_getData(self::EVENT_TYPE);
    }

    /**
     * @param $eventType
     * @return $this
     */
    public function setEventType($eventType)
    {
        return $this->setData(self::EVENT_TYPE, $eventType);
    }

    /**
     * @return mixed
     */
    public function getEntityData()
    {
        $entityData = unserialize($this->_getData(self::ENTITY_DATA));
        return $entityData;
    }

    /**
     * @param $entityData
     * @return $this
     */
    public function setEntityData($entityData)
    {
        $entityData = serialize($entityData);
        return $this->setData(self::ENTITY_DATA, $entityData);
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->_getData(self::CREATED_AT);
    }

    /**
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return mixed
     */
    public function getProcessedAt()
    {
        return $this->_getData(self::PROCESSED_AT);
    }

    /**
     * @param $processedAt
     * @return $this
     */
    public function setProcessedAt($processedAt)
    {
        return $this->setData(self::PROCESSED_AT, $processedAt);
    }

    /**
     * @return mixed
     */
    public function getProcessed()
    {
        return $this->_getData(self::PROCESSED);
    }

    /**
     * @param $processed
     * @return $this
     */
    public function setProcessed($processed)
    {
        return $this->setData(self::PROCESSED, $processed);
    }
}
