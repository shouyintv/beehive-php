<?php
namespace Beehive\Foundation\Event;

use Beehive\Contracts\Event\Emitter as EmitterContracts;

class Emitter implements EmitterContracts 
{
    protected $eventListeners = [];
    public function on($event, callable $listener)
    {
        if (!isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }
        $this->eventListeners[$event][] = $listener;
    }
    public function once($event, callable $listener)
    {
        $onceListener = function () use (&$onceListener, $event, $listener) {
            $this->removeListener($event, $onceListener);
            call_user_func_array($listener, func_get_args());
        };
        $this->on($event, $onceListener);
    }
    public function removeListener($event, callable $listener)
    {
        if (isset($this->eventListeners[$event])) {
            $index = array_search($listener, $this->eventListeners[$event], true);
            if (false !== $index) {
                unset($this->eventListeners[$event][$index]);
            }
        }
    }
    public function removeAlleventListeners($event = null)
    {
        if ($event !== null) {
            unset($this->eventListeners[$event]);
        } else {
            $this->eventListeners = [];
        }
    }
    public function eventListeners($event)
    {
        return isset($this->eventListeners[$event]) ? $this->eventListeners[$event] : [];
    }
    public function emit($event, array $arguments = [])
    {
        foreach ($this->eventListeners($event) as $listener) {
            call_user_func_array($listener, $arguments);
        }
    }
}
