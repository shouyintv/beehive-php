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
abstract class Provider
{
    public function __construct(Connection $connection, Invoker $request, Invoker $response)
    {
        $this->connection = $connection;
        $this->request = $request;
        $this->response = $response;
    }

    public function __invoke()
    {
        $this->invoke();
    }

    public function send()
    {
        $this->response->invoke();
    }

    abstract public function invoke();
}