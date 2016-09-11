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
            'Timer' => Beehive\Facades\Timer::class
        ]
    ]);
    return $config;
});

$app->set('log', function() {
    $multiple = new Phalcon\Logger\Multiple;
    $fileLogger = new Beehive\Logger\Adapter\FileRollSize(__DIR__ . '/../logs/app.log');
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
$app->bootstrap();


$config = App::make('config')->get('app.aliases');
print_r($config);

Log::debug('tttt', ['a' => 'b']);
Log::error('tttt', ['a' => 'b']);
Log::warning('tttt', ['a' => 'b']);
Log::critical('tttt', ['a' => 'b']);
Log::alert('tttt', ['a' => 'b']);
Log::notice('tttt', ['a' => 'b']);
Log::info('tttt', ['a' => 'b', 'ccc' => 'bbb']);
Log::emergency('tttt', ['a' => 'b']);