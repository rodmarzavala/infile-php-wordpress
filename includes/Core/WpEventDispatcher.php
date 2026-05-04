<?php

namespace InfilePhp\WordPress\Core;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * A PSR-14 Event Dispatcher that bridges core SDK events to WordPress hooks.
 */
class WpEventDispatcher implements EventDispatcherInterface
{
    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event
     *   The object to process.
     *
     * @return object
     *   The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event)
    {
        $eventName = get_class($event);

        // Allow WordPress to hook directly into the fully qualified class name
        do_action($eventName, $event);

        // If the event is stoppable, we can't do much in WordPress's do_action since it doesn't support early return,
        // but core events are not stoppable anyway.
        return $event;
    }
}
