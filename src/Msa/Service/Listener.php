<?php
namespace Beehive\Msa\Service;

use Beehive\Contracts\Connection\Connection;
use Beehive\Msa\Protocol\Invoker;
use RuntimeException;

/**
 * 微服务架构客户端
 *
 * @author Ewenlaz
 */
abstract class Listener
{
    public function __construct(Connection $connection, Invoker $packet)
    {
        $this->connection = $connection;
        $this->packet = $packet;
    }

    public function __invoke()
    {
        $this->invoke();
    }
    abstract public function invoke();
}