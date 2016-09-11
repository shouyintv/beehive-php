<?php
namespace Beehive\Logger\Adapter;

use Phalcon\Logger\Adapter\File;
use Phalcon\Logger\Formatter;
use Phalcon\Logger\Formatter\Line;
use FilesystemIterator;
/**
 * 文件输出
 *
 * @author ewenlaz
 */
class FileRollSize extends File
{
    const ROLL_MODE_SIZE = 'size';
    protected $rollType = '';
    protected $rollSize = 0;
    protected $rollNum = 0;
    protected $rollNowNum = 0;
    protected $rollNowSize = 0;
    protected $minFileIndex = 0;
    protected $maxFileIndex = 0;
    protected $option = null;
    protected $file = '';
    public function __construct($file, $option = null, $num = 5, $max = 30)
    {
        //找到最大值....
        $this->rollSize = max($max, 30) * 1024 * 1024;
        $this->rollNum = max($num, 5);

        parent::__construct($file, $option);
        $this->option = $option;
        $this->file = $file;
        if (file_exists($file)) {
            $this->rollNowSize = filesize($file);
        }
        $this->reset($file);
    }

    public function reset($file)
    {
        $path = dirname($file);
        $it = new FilesystemIterator($path);
        $this->minFileIndex = 0;
        $this->maxFileIndex = 0;
        $startFileName = basename($file);
        $this->rollNowNum = 0;
        foreach ($it as $fileinfo) {
            $fileName = $fileinfo->getFilename();
            if ($file != $fileName && strpos($fileName, $startFileName . '.') === 0) {
                $fileIndex = substr($fileName, strlen($startFileName) + 1);
                if (is_numeric($fileIndex)) {
                    $this->rollNowNum ++;
                    $fileIndex = (int) $fileIndex;
                    if ($this->minFileIndex == 0 || $fileIndex <= $this->minFileIndex) {
                        $this->minFileIndex = $fileIndex;
                    }
                    if ($fileIndex >= $this->maxFileIndex) {
                        $this->maxFileIndex = $fileIndex;
                    }
                }
            }
        }

        if ($this->rollNowSize >= $this->rollSize) {
            $this->close();
            $this->maxFileIndex ++;
            if ($this->rollNowNum >= $this->rollNum) {
                unlink($this->file . '.' . $this->minFileIndex);
            }
            rename($this->file, $this->file. '.' . $this->maxFileIndex);
            $this->rollNowSize = 0;
            parent::__construct($this->file, $this->option);
        }
    }

    public function logInternal($message, $type, $time, array $context)
    {
        $message = $this->getFormatter()->format($message, $type, $time, $context);
        $this->rollNowSize += strlen($message);
        fwrite($this->_fileHandler, $message);
        if ($this->rollNowSize >= $this->rollSize) {
            $this->reset($this->file);
        }
    }

        // var fileHandler;

        // let fileHandler = this->_fileHandler;
        // if typeof fileHandler !== "resource" {
        //     throw new Exception("Cannot send message to the log because it is invalid");
        // }

        // fwrite(fileHandler, this->getFormatter()->format(message, type, time, context));
    public function getFormatter()
    {
        if (!$this->_formatter instanceof Formatter) {
            $this->_formatter = new Line(null, 'Y-m-d H:i:s');
        }
        return $this->_formatter;
    }
}