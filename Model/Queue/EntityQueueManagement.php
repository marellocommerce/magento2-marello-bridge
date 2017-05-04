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

use Marello\Bridge\Model\Queue\EntityQueueFactory;
use Marello\Bridge\Model\Processor\ProcessorRegistry;
use Marello\Bridge\Api\EntityQueueManagementInterface;
use Marello\Bridge\Model\EntityRepositoryRegistry;

class EntityQueueManagement implements EntityQueueManagementInterface
{
    /**
     * Maximum number of entities to be sent in one cron run
     */
    protected $batchSize = 50;

    /** @var EntityQueueFactory $entityQueueFactory */
    protected $entityQueueFactory;

    /** @var ProcessorRegistry $processorRegistry */
    protected $processorRegistry;

    /** @var EntityQueueRepository $entityQueueRepository */
    protected $entityQueueRepository;
    
    /** @var EntityRepositoryRegistry $entityRepositoryRegistry */
    protected $entityRepositoryRegistry;

    /**
     * EntityQueueManagement constructor.
     * @param \Marello\Bridge\Model\Queue\EntityQueueFactory $entityQueueFactory
     * @param ProcessorRegistry $processorRegistry
     * @param EntityQueueRepository $entityQueueRepository
     * @param EntityRepositoryRegistry $entityRepositoryRegistry
     */
    public function __construct(
        EntityQueueFactory $entityQueueFactory,
        ProcessorRegistry $processorRegistry,
        EntityQueueRepository $entityQueueRepository,
        EntityRepositoryRegistry $entityRepositoryRegistry
    ) {
        $this->entityQueueFactory       = $entityQueueFactory;
        $this->processorRegistry        = $processorRegistry;
        $this->entityQueueRepository    = $entityQueueRepository;
        $this->entityRepositoryRegistry = $entityRepositoryRegistry;
    }

    /**
     * allow for smaller/bigger batch size by overriding default
     * @param int $size
     */
    public function setBatchSize($size)
    {
        $this->batchSize = $size;
    }

    /**
     * Get current batch size
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * {@inheritdoc}
     * @return EntityQueueManagement
     */
    public function getEntityQueueCollection()
    {
        return $this->getQueueCollection();
    }

    /**
     * {@inheritdoc}
     * @return $this
     */
    protected function getQueueCollection()
    {
        $entityQueueCollection = $this->entityQueueFactory->create()->getCollection();
        $entityQueueCollection->addFilter('processed', ['eq' => 0]);
        return $entityQueueCollection->setPageSize($this->getBatchSize());
    }

    /**
     * {@inheritdoc}
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processQueue()
    {
        $allLoaded = false;
        $curPage = 1;
        $i = 1;

        while (!$allLoaded) {
            $collection = $this->getQueueCollection($curPage);

            if ($collection->getSize() >= $i) {
                try {
                    foreach ($collection as $queueItem) {
                        $eventType = $queueItem->getEventType();
                        $processor = null;
                        if ($eventType) {
                            $processor = $this->getEntityProcessor($eventType);
                        }
                        $entityData = $this->getEntityData($queueItem);
                        $result = $processor->process($entityData);
                        if ($result) {
                            $queueItem->setProcessed(1);
                            $queueItem->setProcessedAt(new \DateTime('now'));
                            $this->entityQueueRepository->save($queueItem);
                        }
                        $i++;
                    }
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Could not process Queue item'));
                    return false;
                }

                $curPage++;
            } else {
                $allLoaded = true;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     * @param $processorAlias
     * @return mixed
     */
    protected function getEntityProcessor($processorAlias)
    {
        $processors = $this->processorRegistry->getProcessors();
        if (!isset($processors[$processorAlias]) && empty($processors[$processorAlias])) {
            throw new \InvalidArgumentException(sprintf('No processor found for alias "%s"', $processorAlias));
        }

        return $processors[$processorAlias];
    }

    /**
     * {@inheritdoc}
     * @param $repositoryAlias
     * @return mixed
     */
    protected function getEntityRepository($repositoryAlias)
    {
        $repositories = $this->entityRepositoryRegistry->getRegisteredRepositories();
        if (!isset($repositories[$repositoryAlias]) && empty($repositories[$repositoryAlias])) {
            throw new \InvalidArgumentException(sprintf('No repository found for alias "%s"', $repositoryAlias));
        }

        return $repositories[$repositoryAlias];
    }

    /**
     * {@inheritdoc}
     * @param $queueItem
     * @return array|bool
     */
    private function getEntityData($queueItem)
    {
        $entityData = $queueItem->getEntityData();

        if (!isset($entityData['entityAlias']) && empty($entityData['entityAlias'])) {
            return false;
        }
        
        $repository = $this->getEntityRepository($entityData['entityAlias']);
        $entity = $repository->get($queueItem->getMagId());
        return [$entityData['entityAlias'] => $entity, 'type' => $queueItem->getEventType()];
    }
}
