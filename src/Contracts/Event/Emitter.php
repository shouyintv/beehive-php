<?php
namespace Beehive\Contracts\Event;

/**
 * 事件触发器
 *
 * @author Ewenlaz
 */
interface Emitter
{
    public function on($event, callable $listener);
    public function once($event, callable $listener);
    public function removeListener($event, callable $listener);
    public function removeAllEventListeners($event = null);
    public function eventListeners($event);
    public function emit($event, array $arguments = []);
}
