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
namespace Marello\Bridge\Cron;

use Marello\Bridge\Api\EntityQueueManagementInterface;

class ProcessEntityQueue
{
    protected $entityQueueManagement;

    public function __construct(EntityQueueManagementInterface $entityQueueManagement)
    {
        $this->entityQueueManagement = $entityQueueManagement;
    }

    /**
     * Add products to changes list with price which depends on date
     * @throws \Exception
     */
    public function execute()
    {
        try {
            $this->entityQueueManagement->processQueue();
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Cannot process queue, message is: %s', $e->getMessage()));
        }
    }
}
