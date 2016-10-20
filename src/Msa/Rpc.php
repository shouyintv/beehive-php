<?php
namespace Beehive\Msa;

use RuntimeException;
use ReflectionClass;
use StdClass;
use Log;
use Swoole\Atomic;
use Beehive\Msa\Protocol\Packet;
use Beehive\Msa\Protocol\Invoker;
use Beehive\Server\Command;
/**
 * 微服务架构客户端
 *
 * @author Ewenlaz
 */

class Rpc extends Command
{
    protected $handlerServices = [];
    protected $container = '';
    protected $force = false;
    protected $callServices = [];
    protected $callingServices = [];

    public function __construct(AsyncClient $container)
    {
        $this->container = $container;
        $this->container->on(AsyncClient::EVENT_RECEIVE, [$this, 'onContainerReceive']);
        $this->container->on(AsyncClient::EVENT_CONNECT, [$this, 'onContainerConnect']);
        $this->atomic = new Atomic;
    }

    public function accept($name = null)
    {
        $this->registerHandler($name, 'accept');
    }

    public function listen($name = null)
    {
        $this->registerHandler($name, 'event');
    }

    public function publish($name = null, $protocol = null)
    {
        if (!$protocol) {
            throw new RuntimeException(sprintf('rpc %s protocol is empty!', $type), 1);
        }
        if (!class_exists($protocol, true)) {
            throw new RuntimeException(sprintf('rpc publish:%s protocol load fail!', $name), 1);
        }
        $handler = new StdClass;
        $handler->name = $name;
        $handler->service = $name;
        $handler->id = crc32($handler->name);
        $handler->protocol = $protocol;
        $this->callServices[$name] = $handler;
    }

    public function remote($name = null, $protocol = null)
    {
        if (!$protocol) {
            throw new RuntimeException(sprintf('rpc %s protocol is empty!', $type), 1);
        }
        if (!class_exists($protocol, true)) {
            throw new RuntimeException(sprintf('rpc remote:%s protocol load fail!', $name), 1);
        }
        $handler = new StdClass;
        $handler->name = $name;
        $handler->service = $name;
        $handler->id = crc32($handler->name);
        $handler->protocol = $protocol;
        $this->callServices[$name] = $handler;
    }

    public function registerHandler($name = null, $type = 'accept')
    {
        //反射Service Handler，提取相应配置数据
        if (!$name) {
            throw new RuntimeException(sprintf('rpc %s handler is empty!', $type), 1);
        }
        if (!class_exists($name, true)) {
            throw new RuntimeException(sprintf('rpc %s:%s handler load fail!', $type, $name), 1);
        }
        $reflection = new ReflectionClass($name);
        $docStr = $reflection->getDocComment();
        preg_match_all('/\@(.*)[ ]+(.*)\n/', $docStr, $matches);
        $docs = array_combine($matches[1], $matches[2]);
        $handler = new StdClass;
        if (isset($docs['name']) && $docs['name']) {
            $handler->name = $docs['name'];
        } else {
            throw new RuntimeException(sprintf('rpc %s:%s @name is empty!', $type, $name), 1);
        }
        $handler->invoker = $name;
        if (isset($docs['service']) && $docs['service']) {
            $handler->service = $docs['service'];
        } else {
            throw new RuntimeException(sprintf('rpc %s:%s @service is empty!', $type, $name), 1);
        }
        if (isset($docs['protocol']) && $docs['protocol']) {
            $handler->protocol = $docs['protocol'];
            if (!class_exists($handler->protocol, true)) {
                throw new RuntimeException(sprintf('rpc %s:%s protocol load fail!', $type, $handler->protocol), 1);
            }
        } else {
            throw new RuntimeException(sprintf('rpc %s:%s @protocol is empty!', $type, $handler->protocol), 1);
        }
        $handler->id = crc32($handler->service);
        $handler->boardcast = false;
        $handler->force = $this->force;
        if (isset($docs['boardcast']) && $docs['boardcast']) {
            $handler->boardcast = $docs['boardcast'] !== 'true' ?: true;
        }
        $this->handlerServices[$handler->id] = $handler;
    }

    public function onContainerReceive($cli, $data)
    {
        //收到容器回来的数据.
        $packet = new Packet([]);
        $packet->unpack($data);

        if ($packet->flag & Packet::FLAG_RESPONSE) {
            //响应
            $askid = $packet->askid;
            if (isset($this->callingServices[$askid])) {
                $callable = $this->callingServices[$askid];
                unset($this->callingServices[$askid]);
                //处理回调
                $newInvoker = clone $callable->invoker;
                $newInvoker->setPacket($packet);
                $newInvoker->unserialize($packet->body);
                $callable->invoker->onResult($newInvoker);
            } else {
                Log::warning(
                    'response not handler',
                    [
                        'service' => $packet->service,
                        'askid' => $packet->askid,
                        'body' => $packet->body,
                        'routers' => $packet->routers_list
                    ]
                );
            }
        } else {
            if (!isset($this->handlerServices[$packet->service])) {
                Log::warning(
                    'can`t hand sevice',
                    [
                        'service' => $packet->service,
                        'askid' => $packet->askid,
                        'body' => $packet->body,
                        'routers' => $packet->routers_list
                    ]
                );
                return false;
            }
            $handler = $this->handlerServices[$packet->service];
            $invokerHandler = $handler->invoker;
            $protocol = $handler->protocol;
            $request = new $protocol([]);
            $request->unserialize($packet->body);
            $request->setPacket($packet);
            if ($packet->flag & Packet::FLAG_EVENT) {
                $invoker = new $invokerHandler($this->container, $request);
            } else {
                $response = new $protocol([]);
                $responsePacket = clone $packet;
                $responsePacket->flag |= Packet::FLAG_RESPONSE;
                $response->setPacket($responsePacket);
                $response->setServiceName($handler->service);
                $response->setInvokerCallable([$this, 'onInvokerInvoke']);
                $invoker = new $invokerHandler($this->container, $request, $response);
            }

            $invoker();
        }
    }

    public function onContainerConnect()
    {
        //开始构造容器连接协议
        $registerInfo = [
            'name' => $this->serverName,
            'id' => $this->serverId,
            'accepts' => []
        ];

        foreach ($this->handlerServices as $handler) {
            $handlerRegister = [
                'service' => $handler->service,
                'boardcast' => $handler->boardcast,
                'force' => $handler->force,
            ];
            $registerInfo['accepts'][] = $handlerRegister;
        }

        $this->instance('Container.Service.Register')
            ->setData($registerInfo)
            ->then([$this, 'onContainerRegister'])
            ->invoke();
    }

    public function instance($name)
    {
        if (!isset($this->callServices[$name])) {
            throw new RuntimeException(sprintf('rpc call:%s fail!', $name), 1);
        }
        $protocol = $this->callServices[$name]->protocol;
        $protocol = new $protocol([]);
        $packet = $protocol->getPacket();
        $packet->askid = $this->atomic->add();
        $packet->time = time();
        $packet->uniqid = $packet->askid;
        $protocol->setServiceName($name);
        $protocol->setInvokerCallable([$this, 'onInvokerInvoke']);
        return $protocol;
    }

    public function onInvokerInvoke($invoker)
    {
        //注册到回调表里
        $packet = $invoker->getPacket();
        $askid = $packet->askid;
        $callable = new StdClass;
        $callable->invoker = $invoker;
        $callable->time = microtime(true);
        $this->callingServices[$askid] = $callable;
        $packet->body = $invoker->serialize();
        $this->container->send($packet->pack());
        Log::debug('packet send', ['name' => $packet->name, 'askid' => $packet->askid, 'body' => $packet->body]);
    }

    public function event($name)
    {
        if (!isset($this->callServices[$name])) {
            throw new RuntimeException(sprintf('rpc event:%s fail!', $name), 1);
        }
        $protocol = $this->callServices[$name]->protocol;
        $protocol = new $protocol([]);
        $packet = $protocol->getPacket();
        $packet->askid = $this->atomic->add();
        $packet->time = time();
        $packet->uniqid = $packet->askid;
        $packet->flag |= PacketInterface::FLAG_EVENT;
        $protocol->setServiceName($name);
        $protocol->setInvokerCallable([$this, 'onInvokerInvoke']);
        return $protocol;
    }

    public function onContainerRegister(Invoker $invoker)
    {
        if ($invoker->isSuccess()) {
            Log::info('container register success');
            $this->emit(static::EVENT_START);
        } else {
            Log::alert('container register fail');
        }
    }

    public function start()
    {
        $this->emit(static::EVENT_INIT);
        $this->container->connect();
    }
}