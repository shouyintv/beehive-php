<?php
namespace Beehive\Msa;

use Beehive\Foundation\Connection\TcpAsyncClient;

/**
 * 微服务架构客户端
 *
 * @author Ewenlaz
 */
class AsyncClient extends TcpAsyncClient
{
    public function __construct($host = '', $port = 0)
    {
        parent::__construct($host, $port);
        //设置包头方式
        $this->setOption([
            'open_length_check'     => true,
            'package_length_type'   => 'N',
            'package_length_offset' => 0,
            'package_body_offset'   => 4,
            'package_max_length'    => 1024 * 8
        ]);
    }
}