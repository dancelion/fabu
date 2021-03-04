<?php
/**
 * Created by PhpStorm.
 * User: dancelion
 * Date: 2019-08-29
 * Time: 16:46
 */

namespace apps\controller;

use apps\model\Apps;
use think\Controller;

class Index extends Controller
{
    public function index($shortUrl = '')
    {
        $info = Apps::where(['shortUrl' => $shortUrl])->find();
        return $this->fetch('', compact('info'));
    }

    public function download($shortUrl = '')
    {
        $app = Apps::with('current')->where(['shortUrl' => $shortUrl])->find();
        if (!$app) $this->error('该应用不存在');
        if (!$app['current']) $this->error('当前无发布版本');
        $url = $app->getDownloadUrl();
        $this->result(['url' => $url], 1, 'success', 'json');
    }

    public function plist($shortUrl = '')
    {
        $app = Apps::with('current')->where(['shortUrl' => $shortUrl])->find();
        if (!$app) die('该应用不存在');
        if (!$app['current']) die('当前无发布版本');
        $downloadUrl = $app->getIpaDownloadUrl();
        $this->assign(compact('app','downloadUrl'));
        return xml($this->fetch());
    }

}