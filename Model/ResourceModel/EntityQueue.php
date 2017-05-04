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
namespace Marello\Bridge\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class EntityQueue extends AbstractDb
{
    const MAIN_TABLE_NAME = 'marello_entity_queue';

    // @codingStandardsIgnoreStart
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE_NAME, 'id');
    }
    // @codingStandardsIgnoreStart

    /**
     * Find a EntityQueue record on magento id and event type
     * @param $magId
     * @param $eventType
     * @return string
     */
    public function findOneByIdAndEventType($magId, $eventType)
    {
        $connection = $this->getConnection();
        $sql = $connection->select()
            ->from($this->getTable(self::MAIN_TABLE_NAME), array('*'))
            ->where('mag_id = ?', $magId)
            ->where('event_type = ?', $eventType);

        return $connection->fetchOne($sql);
    }
}
