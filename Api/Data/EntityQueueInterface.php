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
namespace Marello\Bridge\Api\Data;

interface EntityQueueInterface
{
    /**#@+
     * Constants defined for keys of  data array
     */
    const ID = 'id';

    const MAG_ID = 'mag_id';

    const EVENT_TYPE = 'event_type';

    const ENTITY_DATA = 'entity_data';

    const CREATED_AT = 'created_at';

    const PROCESSED_AT = 'processed_at';
    
    const PROCESSED = 'processed';
    

    /**#@-*/

    /**
     * EntityQueue id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set EntityQueue id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return mixed
     */
    public function getMagId();

    /**
     * @param $magId
     * @return mixed
     */
    public function setMagId($magId);

    /**
     * @return mixed
     */
    public function getEventType();

    /**
     * @param $eventType
     * @return mixed
     */
    public function setEventType($eventType);

    /**
     * @return mixed
     */
    public function getEntityData();

    /**
     * @param $entityData
     * @return mixed
     */
    public function setEntityData($entityData);

    /**
     * @return mixed
     */
    public function getCreatedAt();

    /**
     * @param $createdAt
     * @return mixed
     */
    public function setCreatedAt($createdAt);

    /**
     * @return mixed
     */
    public function getProcessedAt();

    /**
     * @param $processedAt
     * @return mixed
     */
    public function setProcessedAt($processedAt);

    /**
     * @return mixed
     */
    public function getProcessed();

    /**
     * @param $processed
     * @return mixed
     */
    public function setProcessed($processed);
}
