<?php

$loader = new Phalcon\Loader;
$loader->registerNamespaces(array(
    'Beehive' => __DIR__ . '/../src',
));
$loader->register();

$app = new Beehive\Kernel\Application;
$app->set('config', function() {
    $config = new \Phalcon\Config([
        'app.aliases' => [
            'Log' => Beehive\Facades\Log::class,
            'App' => Beehive\Facades\App::class,
            'Timer' => Beehive\Facades\Timer::class,
            'Rpc' => Beehive\Msa\Facades\Rpc::class
        ],
        'server.name' => 'TestSvr',
        'server.id'   => crc32('TestSvr.' . posix_getpid()),
        'server.logDir' => sprintf(__DIR__ . '/../logs/%s.log', 'TestSvr')
    ]);
    return $config;
});

$app->set('log', function() {
    $multiple = new Phalcon\Logger\Multiple;
    $fileLogger = new Beehive\Logger\Adapter\FileRollSize(App::make('config')->get('server.logDir'));
    $fileLogger->setFormatter(new Beehive\Logger\Formatter\Line);
    $fileLogger->setLogLevel(Phalcon\Logger::DEBUG);

    $logger = new Beehive\Logger\Adapter\Console;
    $logger->setFormatter(new Beehive\Logger\Formatter\Console);
    $logger->setLogLevel(Phalcon\Logger::DEBUG);

    $multiple->push($logger);
    $multiple->push($fileLogger);
    return $multiple;
});

$app->set('app', $app);

$app->set('rpc', function() {
    $connection = new Beehive\Msa\AsyncClient('192.168.1.202', 9900);
    return new Beehive\Msa\Rpc($connection);
});
$app->bootstrap();

Rpc::getFacadeRoot()->setServerName(App::make('config')->get('server.name'));
Rpc::getFacadeRoot()->setServerId(App::make('config')->get('server.id'));

Rpc::accept(Beehive\Msa\Service\Ping::class);
// Rpc::listen(Beehive\Msa\Service\PingEvent::class);
Rpc::remote('Container.Service.Register', Beehive\Msa\Protocol\Json::class);
Rpc::remote('TestSvr.Test.Test', Beehive\Msa\Protocol\Json::class);
Rpc::on(Beehive\Server\Command::EVENT_START, function() {
    Log::debug('msa server start', [
        'server' => App::make('config')->get('server.name'),
        'id'     => App::make('config')->get('server.id'),
        'logDir' => App::make('config')->get('server.logDir')
    ]);

    Rpc::
});

Rpc::start();

