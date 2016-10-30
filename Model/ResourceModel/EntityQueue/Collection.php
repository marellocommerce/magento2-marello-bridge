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
namespace Marello\Bridge\Model\ResourceModel\EntityQueue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Marello\Bridge\Model\Queue\EntityQueue;
use Marello\Bridge\Model\ResourceModel\EntityQueue as EntityQueueResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            EntityQueue::class,
            EntityQueueResourceModel::class
        );
    }
}
