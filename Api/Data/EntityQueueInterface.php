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
    
    public function getMagId();
    
    public function setMagId($magId);
    
    public function getEventType();
    
    public function setEventType($eventType);
    
    public function getEntityData();
    
    public function setEntityData($entityData);
    
    public function getCreatedAt();
    
    public function setCreatedAt($createdAt);
    
    public function getProcessedAt();
    
    public function setProcessedAt($processedAt);
    
    public function getProcessed();
    
    public function setProcessed($processed);
}
