<?php


namespace tools;


use think\Exception;

abstract class PackageResolution
{
    protected $file;

    /**
     * PackageResolution constructor.
     * @param $file
     * @throws Exception
     */
    public function __construct($file)
    {
        if(!is_file($file)){
            throw new Exception('文件获取失败');
        }
        $this->file = $file;
    }
    abstract function parse();
}