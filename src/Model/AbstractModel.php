<?php
/**
 * Db\Model
 * 
 * PHP version 7
 */
namespace Cathedral\Db\Model;

use Laminas\Db\TableGateway\Feature\EventFeatureEventsInterface;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\Db\TableGateway\AbstractTableGateway as LaminasAbstractTableGateway;
use Laminas\Db\TableGateway\Feature\EventFeature\TableGatewayEvent;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;

/**
 * AbstractModel
 * @version 1.0.0
 */
class AbstractModel extends LaminasAbstractTableGateway implements EventManagerAwareInterface, EventFeatureEventsInterface {

    /**
     * Set the event manager instance used by this context
     *
     * @param \Laminas\EventManager\EventManagerInterface $eventManager
     * @return DrawsTable
     */
    public function setEventManager(\Laminas\EventManager\EventManagerInterface $eventManager)
    {
        $eventManager->addIdentifiers([
            self::class,
            array_pop(explode('\\', self::class)),
            TableGateway::class,
        ]);
        $this->event = $this->event ?: new TableGatewayEvent();
        $this->eventManager = $eventManager;
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return \Laminas\EventManager\EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->eventManager instanceof EventManagerInterface) $this->setEventManager(new EventManager(new SharedEventManager()));
        return $this->eventManager;
    }

}