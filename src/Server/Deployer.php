<?php
namespace Beehive\Server;

use Log;
use Swoole\Timer;
use Exception;
use Swoole\Http\Client;
use Beehive\AsyncIO\HttpDownload;
use Swoole\Process;

/**
 * App
 *
 * @author Ewenlaz
 */
class Deployer
{
    const STATUS_INIT = 'READY';
    const STATUS_ON_INIT = 'ON_INIT';
    const STATUS_START = 'START';
    const STATUS_ON_START = 'ON_START';
    const STATUS_ON_OFFLINE = 'ON_OFFLINE';

    protected $url = '';
    protected $path = '';
    protected $deploys = [];
    protected $runtimes = [];
    public function __construct($url = '', $path = '')
    {
        $this->url = $url;
        if (!$this->url) {
            throw new Exception("Error Processing Request", 1);
        }
        if (!$path) {
            $path = __DIR__ . '/release/';
        }
        $this->path = $path;
        $idFile = $this->path . '.id';
        if (file_exists($idFile)) {
            $this->id = file_get_contents($idFile);
        } else {
            $this->id = $this->generateMacAddress();
            file_put_contents($idFile, $this->id);
        }
    }

    public static function generateMacAddress()
    {
        $vals = [
            '0', '1', '2', '3', '4', '5', '6', '7',
            '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'
        ];
        if (count($vals) >= 1) {
            $mac = array("00"); // set first two digits manually
            while (count($mac) < 6) {
                shuffle($vals);
                $mac[] = $vals[0] . $vals[1];
            }
            $mac = implode(":", $mac);
        }
        return $mac;
    }

    public function syncTask()
    {
        //检查资源状态...
        Log::debug('deployer start tick');
        $uri = parse_url($this->url . '/deploy/check');
        $isHttps = $uri['scheme'] == 'https' ? true : false;
        $defaultPort = $isHttps ? 443 : 80;
        $port = !isset($uri['port']) || !$uri['port'] ? $defaultPort : (int) $uri['port'];
        $uri['isHttps'] = $isHttps;
        $uri['port'] = $port;
        swoole_async_dns_lookup($uri['host'], function($host, $ip) use ($uri) {
            $http = new Client($ip, $uri['port']);
            $header = [
                'host' => $host
            ];
            $http->setHeaders($header);
            $http->post($uri['path'], $this->getCheckData(), [$this, 'onCheckResponse']);
        });
    }

    public function syncStatusToScheduler()
    {
        //检查资源状态...
        Log::debug('deployer start tick');
        $uri = parse_url($this->url . '/deploy/status');
        $isHttps = $uri['scheme'] == 'https' ? true : false;
        $defaultPort = $isHttps ? 443 : 80;
        $port = !isset($uri['port']) || !$uri['port'] ? $defaultPort : (int) $uri['port'];
        $uri['isHttps'] = $isHttps;
        $uri['port'] = $port;
        swoole_async_dns_lookup($uri['host'], function($host, $ip) use ($uri) {
            $http = new Client($ip, $uri['port']);
            $header = [
                'host' => $host
            ];
            $http->setHeaders($header);
            $http->post($uri['path'], [
                'id' => $this->id,
                'hostname' => gethostname(),
                'param' => [
                    'deploys' => $this->deploys,
                    'runtimes' => $this->runtimes
                ]
            ], function($cli) {
                $cli->close();
            });
        });
    }

    public function instanceTask($deploy)
    {
        //检查资源准备状态
        $name = '%s@%s#%d';
        $instance = $deploy['instance'];
        $taskId = $deploy['taskId'];
        $server = sprintf('%s@%s', $deploy['name'], $deploy['version']);
        $rootPath = $this->path . $deploy['name'] . '/';

        if (!is_dir($rootPath)) {
            mkdir($rootPath);
        }
        $versionDir = $rootPath . $deploy['version'] . '/';
        if (!is_dir($versionDir)) {
            mkdir($versionDir);
        }
        $fileExt = $deploy['repository']['type'] == 'zip' ? '.zip' : '.tar.gz';
        $filename = $server . $fileExt;
        $this->deploys[$server] = (object) [
            'name' => $server,
            'status' => static::STATUS_INIT,
            'complete' => false,
            'action' => $deploy['action'],
            'taskId' => $deploy['taskId'],
            'instance' => [],
            'path' => $versionDir,
            'filename' => $filename,
            'deploy' => $deploy,
            'pid' => 0
        ];

        for ($i = 1; $i <= $instance; $i++) {
            $instanceName = sprintf($name, $deploy['name'], $deploy['version'], $i);
            $this->deploys[$server]->instance[$instanceName] = false;
        }
    }

    public function doTask()
    {
        foreach ($this->deploys as $server => $task) {
            if ($task->complete) {
                continue;
            }
            switch ($task->status) {
                case static::STATUS_INIT:
                    $this->deployInit($task);
                    break;
                case static::STATUS_ON_INIT:
                    //$this->deployInit($task);
                    break;
                case static::STATUS_START:
                    $this->deployStart($task);
                    //$this->deployInit($task);
                    break;
                case static::STATUS_ON_START:
                    //$this->deployInit($task);
                    break;
                default:
                    # code...
                    break;
            }
            //if ($task)
        }
        time() % 5 == 0 && $this->syncStatusToScheduler();
    }

    public function deployInit($task)
    {
        Log::info('deployer init', (array) $task);
        $process = new Process(function($process) use ($task) {
            Log::info('start download.......');
            $repository = $task->deploy['repository'];
            $cmd = sprintf(
                'cd %s && wget -O %s -c %s 2>&1 > /dev/null',
                $task->path,
                $task->filename,
                $repository['url']
            );
            if ($repository['type'] == 'tar') {
                $unpackCmd = sprintf(
                    'cd %s && tar zxvf %s 2>&1 > /dev/null',
                    $task->path,
                    $task->filename
                );
            } else {
                $unpackCmd = sprintf(
                    'cd %s && unzip %s -d ./%s',
                    $task->path,
                    $task->filename,
                    'source'
                );
            }
            $retval = null;
            $output = '';
            $ret = exec($cmd, $output, $retval);
            Log::info('start download.', ['cmd' => $cmd]);
            if (!$retval) {
                $retval = null;
                Log::info('start unpackCmd.', ['cmd' => $unpackCmd]);
                $ret = exec($unpackCmd, $output, $retval);
            }
            exit($retval);
        });
        $process->start();
        $task->pid = $process->pid;
        if ($process->pid) {
            $task->status = static::STATUS_ON_INIT;
        }
    }

    public function deployStart($task)
    {
        Log::info('deployer start', (array) $task);
        foreach ($task->instance as $name => &$complete) {
            if ($complete) {
                continue;
            }
            $config = [
                'name' => $name,
                'script' => 'start.sh',
                'cwd' => $task->path . 'source/',
                'args' => '',
                'exec_mode' => 'fork',
                'env' => [
                    'AUTO_DEPLOYER' => '1'
                ],
                'exec_interpreter' => 'sh'
            ];

            file_put_contents($task->path . 'pm2.json', json_encode($config));
            if (isset($this->runtimes[$name]) && $task->action === 'deploy') {
                $complete = true;
                continue;
            }
            $retval = 0;
            $output = '';
            if ($task->action === 'offline') {
                Log::warning('deployer intance offline', ['name' => $name]);
                exec(sprintf('cd %s && pm2 delete pm2.json -s', $task->path), $output, $retval);
                $task->status = static::STATUS_ON_OFFLINE;
            } elseif (!isset($this->runtimes[$name]) || $task->action === 'reload') {
                Log::warning('deployer intance startOrGracefulReload', ['name' => $name]);
                exec(sprintf('cd %s && pm2 startOrGracefulReload pm2.json -s', $task->path), $output, $retval);
                $task->status = static::STATUS_ON_START;
            }

            $complete = true;
        }
        $isAllComplete = true;
        foreach ($task->instance as $name => $complete) {
            if (!$complete) {
                $isAllComplete = false;
                break;
            }
        }
        $task->complete = $isAllComplete;
    }

    public function onSignal($signal) {
        while($ret = Process::wait(false)) {
            foreach ($this->deploys as $server => $task) {
                if ($task->pid == $ret['pid']) {
                    $task->pid = 0;
                    $task->status = $ret['code'] ? static::STATUS_INIT : static::STATUS_START;
                }
            }
        }
    }

    public function onCheckResponse($cli)
    {
        if ($cli->errCode || $cli->statusCode != 200) {
            Log::warning('check error', [
                'errCode' => $cli->errCode,
                'statusCode' => $cli->statusCode
            ]);
            return;
        }
        $data = $cli->body;
        $data = json_decode($data, true);
        if ($data['code'] == 1) {
            Log::debug('deployer hash is verified');
            //Hash不变时CheckRutime
            $allowInstance = [];
            foreach ($this->deploys as $deploy) {
                $allowInstance = array_merge($allowInstance, $deploy->instance);
            }
            foreach ($this->runtimes as $name => $instance) {
                if (!isset($allowInstance[$name])) {
                    Log::debug('deployer instance delete warning', ['instance' => $name]);
                    exec(sprintf('pm2 delete %s', $name));
                    unset($this->runtimes[$name]);
                }
            }
        } else {
            foreach ($this->deploys as $server => &$task) {
                //先标记为离线....
                $task->action = 'offline';
            }
            //$list = exec('pm2 jlist');
            $deploys = $data['data']['deploys'];
            //重新标记状态标记
            foreach ($deploys as $deploy) {
                Log::debug('deployer instance task', $deploy);
                $this->instanceTask($deploy);
            }
        }
        $cli->close();
    }

    public function getCheckData()
    {
        //计算hash --
        $hashDiffMap = [];
        foreach ($this->deploys as $server) {
            $hashDiffMap[] = $server->taskId;
        }
        sort($hashDiffMap);
        $hash = md5(implode('', $hashDiffMap)); 
        return [
            'id' => $this->id,
            'hostname' => gethostname(),
            'param' => [
                'hash' => $hash
            ]
        ];
    }

    public function syncPm2Task()
    {
        //同步pm2 状态
        Log::debug('deployer sync pm2 runtimes');
        $pm2Json = exec('pm2 jlist');
        $jlist = json_decode($pm2Json, true);
        $this->runtimes = [];
        foreach ($jlist as $svr) {
            if (!isset($svr['pm2_env']['AUTO_DEPLOYER'])) {
                continue;
            }
            $this->runtimes[$svr['name']] = [
                'id' => $svr['pm_id'],
                'pid' => $svr['pid'],
                'name' => $svr['name'],
                'monit' => $svr['monit'],
                'status' => $svr['pm2_env']['status'],
                'uptime' => (int) ($svr['pm2_env']['pm_uptime'] / 1000),
                'created' => (int) ($svr['pm2_env']['created_at'] / 1000),
            ];
        }
        Log::debug('deployer status', ['instance' => count($this->runtimes)]);
    }

    public function start()
    {
        Log::debug('deployer start....');
        $this->syncPm2Task();
        $this->syncTask();
        Timer::tick(15000, [$this, 'syncTask']);
        Timer::tick(10000, [$this, 'syncPm2Task']);
        Timer::tick(1000, [$this, 'doTask']);
        Process::signal(SIGCHLD, [$this, 'onSignal']);
    }
}