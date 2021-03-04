<?php


namespace tools;


use ApkParser\Parser;
use think\Exception;

class Apk extends PackageResolution
{

    function parse()
    {
        $apk = new Parser($this->file);
        $manifest = $apk->getManifest();
//        $permissions = $manifest->getPermissions(); 权限列表
        $labelResourceId = $apk->getManifest()->getApplication()->getLabel();
        $appLabel = $apk->getResources($labelResourceId);
        $resourceId = $apk->getManifest()->getApplication()->getIcon();
        $resources = $apk->getResources($resourceId);
        $logoPath = $this->saveLogo(stream_get_contents($apk->getStream(array_shift($resources))));

        $info = [
            'appName' => $appLabel[0],
            'versionCode' => $manifest->getVersionCode(),
            'bundleId' => $manifest->getPackageName(),
            'versionStr' => $manifest->getVersionName(),
            'platform' => 'android',
            'icon' => $logoPath
        ];
        return $info;
    }

    /**
     * 把应用图标保存到文件
     * @param $base64_data
     * @return string
     * @throws Exception
     */
    protected function saveLogo($base64_data)
    {
        if (empty($base64_data)) {
            throw new Exception('图标获取失败');
        }
        $dir_info = pathinfo($this->file);
        $path = cmf_get_root() . $dir_info['dirname'] . '/';
        $user_path = $dir_info['filename'] . '.png';
        if (@file_put_contents($path . $user_path, $base64_data)) {
            return $dir_info['dirname'].'/'.$user_path;
        }
        throw new Exception('图标获取失败');
    }
}