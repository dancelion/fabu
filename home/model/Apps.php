<?php

namespace apps\model;

use cmf\lib\Storage;
use think\Model;

class Apps extends Model
{
    public function current()
    {
        return $this->hasOne('Versions', 'id', 'releaseVersionId');
    }

    public function getDownloadUrl()
    {
        if ($this->{'platform'} == 'android') {
            return $this->getQiniuUrl();
        }
        return $this['current']['installUrl'];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function getQiniuUrl()
    {
        $this->setInc('totalDownloadCount');
        $this->{'current'}->setInc('downloadCount');
        $storage = cmf_get_option('storage');
        $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
        return $storage->getFileDownloadUrl($this['current']['downloadUrl']);
    }

    public function getIpaDownloadUrl()
    {
        return $this->getQiniuUrl();
    }
}