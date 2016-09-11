<?php
namespace Beehive\Server;

use Log;
use App;

/**
 * 注册中心
 * 
 * 提供IP查询，目录注册、目录查询, 依赖Beehive\Storage\SortedSet
 *
 * @author Ewenlaz
 */
class Registry extends Http
{
    public function __construct($ip, $port, $mode = SWOOLE_BASE, $flag = SWOOLE_SOCK_TCP)
    {
        parent::__construct($ip, $port, $mode, $flag);
        $this->on('request', [$this, 'onRequest']);
    }

    public function onRequest($request, $response)
    {
        $api = trim($request->server['path_info'], '/');
        $ip = $request->server['remote_addr'];
        $response->header('Content-Type', 'application/json');
        switch ($api) {
            case 'common/ip':
                //查询ip
                $ret = ['ip' => $ip];
                $response->end($this->getSuccessResponseData($ret));
                break;
            case 'config/get':
                //查询目录信息
                $path = $request->post['path'];
                $path = 'registry_sortedset.' . $path;
                //获取5分钟内的配置
                $ret = App::make('registry_sortedset')
                        ->rangeByScore(
                            $path, 
                            time(), 
                            time() + 86400
                        );
                foreach ($ret as &$v) {
                    $v = json_decode($v);
                }
                $response->end($this->getSuccessResponseData($ret));
                break;
            case 'config/keeplive':
                //保持连接
                $path = $request->post['path'];
                $liveTime = $request->post['liveTime'];
                $data = $request->post['data'];
                $path = 'registry_sortedset.' . $path;
                App::make('registry_sortedset')
                        ->add(
                            $path,
                            json_encode($data),
                            time() + $liveTime
                        );
                $response->end($this->getSuccessResponseData());
                break;
            default:
                $response->end($this->getSuccessResponseData());
        }
    }

    protected function getSuccessResponseData($data = [], $message = 'message')
    {
        $ret = [
            'code' => 0,
            'message' => $message,
            'data' => $data
        ];
        return json_encode($ret);
    }
}