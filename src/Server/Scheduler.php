<?php
namespace Beehive\Server;

use Log;
use Swoole\Timer;
use Exception;
use Swoole\Table;

/**
 * App
 *
 * @author Ewenlaz
 */
class Scheduler extends Http
{
    public $deploys = [];
    public $machines = [];
    public $tableHosts = [];
    public function __construct($ip, $port, $mode = SWOOLE_BASE, $flag = SWOOLE_SOCK_TCP)
    {
        parent::__construct($ip, $port, $mode, $flag);
        //主机列表
        // $this->tableHosts = new __construct(8192);
        // $this->tableHosts->column('id', Table::TYPE_STRING);
        // $this->tableHosts->column('hostname', Table::TYPE_STRING, 100);
        // $this->tableHosts->column('ip', Table::TYPE_INT);
        // $this->tableHosts->column('status', Table::TYPE_INT);
        // $this->tableHosts->create();

        // //部署列表
        // $this->deployHosts = new __construct(8192);
        // $this->tableHosts->column('name', Table::TYPE_STRING, 100);
        // $this->tableHosts->column('title', Table::TYPE_STRING, 100);
        // $this->tableHosts->column('version', Table::TYPE_STRING, 100);
        // $this->tableHosts->column('taskId', Table::TYPE_STRING, 32);
        // $this->tableHosts->column('md5', Table::TYPE_STRING, 32);
        // $this->tableHosts->column('instance', Table::TYPE_STRING, 32);


        // $this->tableHosts->column('ip', Table::TYPE_INT);
        // $this->tableHosts->column('status', Table::TYPE_INT);
        // $this->tableHosts->create();



        $this->on('request', [$this, 'onRequest']);
    }

    public function onRequest($request, $response)
    {
        //构造输入输出。。。。
        print_r($request);
        $api = trim($request->server['path_info'], '/');
        Log::info('api request', $request->server);
        $ip = $request->server['remote_addr'];
        $response->header('Content-Type', 'application/json');
        switch ($api) {
            case 'deploy/check':
                //获取Hash....
                $id = $request->post['id'];
                $hostname = $request->post['hostname'];
                $param = $request->post['param'];
                $deployerHash = $param['hash'];
                if (!isset($this->machines[$id])) {
                    $this->machines[$id] = [
                        'id' => $id,
                        'hostname' => $hostname,
                        'liveTime' => time()
                    ];
                }
                $this->machines[$id]['hostname'] = $hostname;
                $this->machines[$id]['liveTime'] = time();
                $deploy = isset($this->deploys[$id]) ? $this->deploys[$id] : null;
                $deployContainer = [
                    'version' => '1.0.1',
                    'name' => 'harbor',
                    'title' => '港口服务',
                    'taskId' => md5('d111111111'),
                    'md5' => md5('harbor'),
                    'instance' => 2,
                    'action' => 'reload',
                    'repository' => [
                        'type' => 'tar',
                        'url' => 'http://h5.zhangxiu.tv/html/repository/habor-1.0.1/habor-1.0.1.tar.gz'
                    ]
                ];

                if (!$deploy) {
                    $deploy = [$deployContainer];
                }

                //计算hash --
                $hashDiffMap = [];
                foreach ($deploy as $server) {
                    $hashDiffMap[] = $server['taskId']; 
                }
                sort($hashDiffMap);
                $hash = md5(implode('', $hashDiffMap)); 
                Log::debug(sprintf('hash:%s,%s', $deployerHash, $hash));
                if ($deployerHash == $hash) {
                    $req = [
                        'rid' => '124',
                        'code' => 1,
                        'message' => '11111',
                        'data' => []
                    ];
                } else {
                    $req = [
                        'rid' => '124',
                        'code' => 0,
                        'message' => '11111',
                        'data' => ['hash' => $hash, 'deploys' => [$deployContainer]]
                    ];
                }
                $response->end(json_encode($req));
                Log::info('deploy/check');
                break;
            
            default:
                
                break;
        }
        // $service = new \Example\HttpProvider\Service\Test;
        // $service();
        // $response->end('xxxxx');
    }
}