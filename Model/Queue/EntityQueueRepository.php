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
namespace Marello\Bridge\Model\Queue;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\DataObject;

use Marello\Bridge\Api\Data\EntityQueueInterface;
use Marello\Bridge\Api\EntityQueueRepositoryInterface;
use Marello\Bridge\Model\ResourceModel\EntityQueue as EntityQueueResource;

class EntityQueueRepository implements EntityQueueRepositoryInterface
{
    /**
     * @var EntityQueueResource
     */
    protected $queueResource;

    /**
     * EntityQueueRepository constructor.
     * @param EntityQueueResource $queueResource
     */
    public function __construct(
        EntityQueueResource $queueResource
    ) {
        $this->queueResource = $queueResource;
    }

    /**
     * {@inheritdoc}
     */
    public function save(EntityQueueInterface $entityQueue)
    {
        try {
            $this->queueResource->save($entityQueue);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Cannot delete queue with id %1',
                    $entityQueue->getId()
                ),
                $e
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     * @param $magId
     * @param $eventType
     * @return string
     */
    public function findOneByIdAndEventType($magId, $eventType)
    {
        return $this->queueResource->findOneByIdAndEventType($magId, $eventType);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(EntityQueueInterface $entityQueue)
    {
        try {
            $this->queueResource->delete($entityQueue);
        } catch (\Exception $e) {
            throw new StateException(
                __(
                    'Cannot delete queue with id %1',
                    $entityQueue->getId()
                ),
                $e
            );
        }

        return true;
    }
}
