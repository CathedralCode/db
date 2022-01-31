<?php
/**
 * Db\Model
 *
 * PHP version 8
 */

declare(strict_types=1);

namespace Cathedral\Db\Model;

use Laminas\Db\TableGateway\AbstractTableGateway as LaminasAbstractTableGateway;

use Laminas\Db\TableGateway\Feature\{
    EventFeature\TableGatewayEvent,
    EventFeatureEventsInterface
};
use Laminas\EventManager\{
    EventManager,
    EventManagerAwareInterface,
    EventManagerInterface,
    SharedEventManager
};

/**
 * AbstractModel
 *
 * @version 1.0.0
 * @package Cathedral\Db
 */
class AbstractModel extends LaminasAbstractTableGateway implements EventManagerAwareInterface, EventFeatureEventsInterface {
    /**
     * @var TableGatewayEvent Event
     */
    protected $event = null;

    /**
     * @var EventManagerInterface EventManager
     */
    protected $eventManager = null;

    /**
     * Set the event manager instance used by this context
     *
     * @param \Laminas\EventManager\EventManagerInterface $eventManager
     * @return DrawsTable
     */
    public function setEventManager(\Laminas\EventManager\EventManagerInterface $eventManager) {
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
    public function getEventManager() {
        if (!$this->eventManager instanceof EventManagerInterface) $this->setEventManager(new EventManager(new SharedEventManager()));
        return $this->eventManager;
    }
}
