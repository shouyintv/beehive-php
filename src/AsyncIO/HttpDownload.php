<?php
/**
 * DownloadV2
 * 抓取文件
 *
 * @author: Cyw
 * @email: rose2099.c@gmail.com
 * @created: 16/5/20 上午11:19
 * @logs:
 *
 */
namespace Beehive\AsyncIO;

class HttpDownload
{
    const STATE_START = 1; //开始下载
    const STATE_END = 2; //下载完成
    const STATE_NOT_FOUND = 3; //无法请求到资源
    const STATE_TIMEOUT = 4; //请求超时
    const STATE_ERROR = 5; //请求超时
    const STATE_UNKOWN_HOST = 6; //域名无法解析

    protected $sourceUri; //数据源
    protected $callback = array(); //回调
    protected $client;
    protected $cancel = false;
    public $total = 0.0001;
    public $progress = 0;
    public $receiveProgress = 0;
    protected $savePath;
    protected $saveData = null;
    protected $startTime = 0;
    protected $code = 200;
    protected $state = 0;
    public $stopCallback = false;
    protected $async = 1;

    /**
     * DownloadV2 constructor.
     * 检查相关权限
     *
     * @param $sourceUri
     * @param $savePath
     */
    public function __construct($sourceUri, $savePath, $async = true)
    {
        if (PHP_SAPI != 'cli') {
            throw new \Exception("非CLI环境下无法调用", 1);
        }
        if (file_exists($savePath)) {
            unlink($savePath);
        }
        if (!$this->mkdir(dirname($savePath))) {
            throw new \Exception("没有创建目录权限，请检查设置", 2);
        }
        $this->savePath = $savePath;
        $this->sourceUri = $sourceUri;

        if (!$async)
        {
            $this->async = 0;
        }
    }


    /**
     * attach
     * progress,state
     *
     * @param $event
     * @param $callback
     * @return mixed
     */
    public function attach($event, $callback)
    {
        return $this->callback[$event] = $callback;
    }

    /**
     * 事件-状态
     *
     * @param $state
     * @param array $data
     * @return bool
     */
    public function eventState($state, $data = [])
    {
        if (isset($this->callback['state']) && !$this->stopCallback) {
            $data = array_merge(['state' => $state], $data);
            return call_user_func_array($this->callback['state'], [$data]);
        }

        return false;
    }

    /**
     * 事件-进程
     *
     * @return bool
     */
    public function eventProgress()
    {
        static $lastProgress = 0;
        if (isset($this->callback['progress']) && !$this->stopCallback &&
            (($this->progress - $lastProgress) / $this->total > 0.01 || $this->progress >= $this->total)
        ) {
            $lastProgress = $this->progress;
            $data = [
                'progress' => $this->progress,
                'total' => $this->total,
                'start_time' => $this->startTime
            ];

            return call_user_func_array($this->callback['progress'], [$data]);
        }
        return false;
    }

    /**
     * 开始下载
     *
     * @return bool
     */
    public function start()
    {
        $this->startTime = time();
        $this->url = parse_url($this->sourceUri);
        swoole_async_dns_lookup($this->url['host'], [$this, 'down']);
        return true;
    }

    public function down($host, $ip)
    {
        if (!$ip) {
            $this->state = self::STATE_NOT_FOUND;
            $this->eventState(self::STATE_NOT_FOUND);
            $this->stopCallback = true;
            return false;
        }
        $url = $this->url;
        $url['ip'] = $ip;
        $url['port'] = isset($url['port']) ? $url['port'] : 80;
        $cli = $this->client = new \Swoole\Client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);
        $cli->on("connect", function ($cli) use ($url) {
            $file = $url['path'] . (isset($url['query']) ? '?' . $url['query'] : '');
            $sendData = "GET {$file} HTTP/1.1\r\n";
            $sendData .= "Host: {$url['host']}\r\n";
            $sendData .= "Connection: keep-alive\r\n";
            $sendData .= "Content-Type:application/x-www-form-urlencoded;charset=utf-8\r\n";
            $sendData .= "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) ";
            $sendData .= "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36\r\n";
            $sendData .= "Accept: */*\r\n";
            $sendData .= "\r\n";
            $cli->send("{$sendData}");
        });
        $cli->on("receive", function ($cli, $data) {
            $body = '';
            if (strpos($data, "\r\n\r\n")) {
                list($headerInfo, $body) = explode("\r\n\r\n", $data);
                if ($body) {
                    $this->state = self::STATE_START;
                    $this->eventState(self::STATE_START);
                }
                $headerInfo = explode("\r\n", $headerInfo);
                $headers = [];
                foreach ($headerInfo as $key => $v) {
                    if ($key === 0) {
                        $this->code = explode(" ", $v)[1];
                    }
                    if (strpos($v, ":") === false) {
                        continue;
                    }
                    list($type, $val) = explode(":", $v);
                    $headers[strtolower(trim($type))] = trim($val);
                }
                if (isset($headers['content-length'])) {
                    $this->total = $headers['content-length'];
                }
                if ($this->code != 200) {
                    if ($this->code == 408) {
                        $this->state = self::STATE_TIMEOUT;
                        $this->eventState(self::STATE_TIMEOUT);
                        $this->stopCallback = true;
                    } else {
                        $this->state = self::STATE_NOT_FOUND;
                        $this->eventState(self::STATE_NOT_FOUND);
                        $this->stopCallback = true;
                    }
                }
            } else {
                if ($this->state == 0) {
                    $this->state = self::STATE_START;
                    $this->eventState(self::STATE_START);
                }
                $body = $data;
            }

            //增加进度值
            $this->receiveProgress += strlen($body);

            if ($this->receiveProgress == $this->total) {
                $this->fileAppend($body, $this->receiveProgress >= $this->total);
                $cli->close();
            }

            if ($this->receiveProgress > $this->total) {
                $cli->close();
            }

            if ($this->receiveProgress < $this->total) {
                $this->fileAppend($body, $this->receiveProgress >= $this->total);
            }

            if ($this->cancel) {
                $cli->close();
                //以及其他回滚操作
                return false;
            }

            return true;
        });

        $cli->on("error", function ($cli) {
            $this->eventState(self::STATE_ERROR);
            $this->stopCallback = true;
            echo "error\n";
        });

        $cli->on("close", function ($cli) {
            //设为进度100%？
            echo "Connection close\n";
        });

        if (!$cli->connect($ip, $url['port'])) {
            $this->eventState(self::STATE_ERROR);
            $this->stopCallback = true;
            echo "Error: " . swoole_strerror($cli->errCode) . "[{$cli->errCode}]\n";
        }
        return true;
    }

    protected function fileAppend($data, $force = false)
    {
        static $lastPosition = 0;
        if ($this->code != 200) {
            return false;
        }
        $this->eventProgress();
        $this->saveData .= $data;
        if ($this->saveData && (strlen($this->saveData) > 1024 * 100 || $force)) {
            $fetch = $this;

            if (!$this->async)
            {
                file_put_contents(
                    $this->savePath, $this->saveData, FILE_APPEND
                );
                $fetch->progress += strlen($this->saveData);
                $over = $fetch->progress >= $fetch->total;
                if ($over) {
                    $fetch->eventProgress();
                    $fetch->eventState(self::STATE_END);
                    $fetch->stopCallback = true;
                }
            } else {
                swoole_async_write(
                    $this->savePath,
                    $this->saveData,
                    $lastPosition,
                    function ($file, $written) use ($fetch) {
                        $fetch->progress += $written;
                        $over = $fetch->progress >= $fetch->total;
                        if ($over) {
                            $fetch->eventProgress();
                            $fetch->eventState(self::STATE_END);
                            $fetch->stopCallback = true;
                        }
                        return true;
                    }
                );
            }

            $lastPosition += strlen($this->saveData);
            $this->saveData = null;
        } else {
            return true;
        }
    }

    public function cancel()
    {
        $this->cancel = true;
    }

    public function setSavePath($savePath)
    {
        $this->savePath = $savePath;
        return true;
    }

    /**
     * 创建文件夹
     *
     * @param $dir
     * @param int $mode
     * @return bool
     */
    private function mkdir($dir, $mode = 0755)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) {
            return true;
        }
        if (!$this->mkdir(dirname($dir), $mode)) {
            return false;
        }
        return @mkdir($dir, $mode);
    }
}
