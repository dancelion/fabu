<?php


namespace app\apps\model;


use think\Model;

class VersionModel extends Model
{
    protected $name='versions';
    protected $autoWriteTimestamp = true;

    public function app()
    {
        return $this->belongsTo('AppsModel','appId');
    }
}