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
namespace Marello\Bridge\Api;

use Marello\Bridge\Api\Data\EntityQueueInterface;

interface EntityQueueRepositoryInterface
{
    /**
     * @param EntityQueueInterface $entityQueue
     * @return mixed
     */
    public function save(EntityQueueInterface $entityQueue);

    /**
     * @param EntityQueueInterface $entityQueue
     * @return mixed
     */
    public function delete(EntityQueueInterface $entityQueue);

    /**
     * @param $magId
     * @param $eventType
     * @return mixed
     */
    public function findOneByIdAndEventType($magId, $eventType);
}
