<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kane <chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\apps\controller;

use app\apps\model\AppsModel;
use app\apps\model\VersionModel;
use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use think\Db;
use think\Exception;
use think\facade\View;
use think\File;
use tools\Apk;
use tools\Ipa;

/**
 * 附件上传控制器
 * Class Asset
 * @package app\asset\controller
 */
class AssetController extends AdminBaseController
{
    protected $userId;

    public function initialize()
    {
        $adminId = cmf_get_current_admin_id();
        $userId = cmf_get_current_user_id();
        if (empty($adminId) && empty($userId)) {
            $this->error("非法上传！");
        }
        $this->userId = $adminId;
    }

    /**
     * webuploader 上传
     */
    public function webuploader()
    {
        if ($this->request->isPost()) {
            $uploader = new Upload();
            $result = $uploader->upload();
            if ($result === false) {
                $this->error($uploader->getError());
            }

            $ext = strtolower(cmf_get_file_extension($result['name']));
            if ($ext == 'apk') {
                $parser = (new Apk('upload/' . $result['filepath']));
            } elseif ($ext == 'ipa') {
                $parser = (new Ipa('upload/' . $result['filepath']));
            } else {
                throw new Exception('文件类型有误,仅支持IPA或者APK文件的上传.');
            }
            $info = $parser->parse();

            $app = AppsModel::where([
                'platform' => $info['platform'],
                'bundleId' => $info['bundleId'],
                'creatorId' => cmf_get_current_admin_id()
            ])->find();
            if (!$app) {
                $data = array_merge($info, [
                    'creatorId' => cmf_get_current_admin_id(),
                    'shortUrl' => cmf_random_string(),
                    'currentVersion' => $info['versionCode'],
                    'downloadUrl' => $result['filepath'],
                ]);
                Db::startTrans();
                try {
                    $appModel = new AppsModel();
                    $appModel->allowField(true)->save($data);
                    $data['appId'] = $appModel->{'id'};
                    $data['size'] = (new File('upload/'.$result['filepath']))->getSize();
                    $data['uploadId'] = cmf_get_current_admin_id();
                    if ($data['platform'] == 'ios') {
                        $data['installUrl'] = Ipa::mapInstallUrl($data['shortUrl']);
                    } else {
                        $data['installUrl'] = $data['downloadUrl'];
                    }
                    $versionModel = new VersionModel();
                    $versionModel->allowField(true)->save($data);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error("数据保存失败!");
                }
                $this->success("上传成功!", '', $result);
            }
            $version = VersionModel::where(['appId' => $app['id'], 'versionCode' => $info['versionCode']])->find();
            if (!$version) {
                $data = array_merge($info, [
                    'uploadId' => cmf_get_current_admin_id(),
                    'size' => (new File('upload/'.$result['filepath']))->getSize(),
                    'appId' => $app['id'],
                    'currentVersion' => $info['versionCode'],
                    'downloadUrl' => $result['filepath'],
                ]);
                if ($data['platform'] == 'ios') {
                    $data['installUrl'] = Ipa::mapInstallUrl($app['appId']);
                } else {
                    $data['installUrl'] = $data['downloadUrl'];
                }
                Db::startTrans();
                try {
                    $versionModel = new VersionModel();
                    $versionModel->allowField(true)->save($data);
                    AppsModel::where(['id' => $app['id']])->update(['currentVersion' => $data['versionCode']]);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error("数据保存失败!");
                }
                $this->success("上传成功!", '', $result);
            } else {
                $this->error('当前版本已存在');
            }
        } else {
            $uploadSetting = cmf_get_upload_setting();

            $arrFileTypes = [
                'image' => ['title' => 'Image files', 'extensions' => $uploadSetting['file_types']['image']['extensions']],
                'video' => ['title' => 'Video files', 'extensions' => $uploadSetting['file_types']['video']['extensions']],
                'audio' => ['title' => 'Audio files', 'extensions' => $uploadSetting['file_types']['audio']['extensions']],
                'file' => ['title' => 'Custom files', 'extensions' => $uploadSetting['file_types']['file']['extensions']]
            ];

            $arrData = $this->request->param();
            if (empty($arrData["filetype"])) {
                $arrData["filetype"] = "image";
            }

            $fileType = $arrData["filetype"];

            if (array_key_exists($arrData["filetype"], $arrFileTypes)) {
                $extensions = $uploadSetting['file_types'][$arrData["filetype"]]['extensions'];
                $fileTypeUploadMaxFileSize = $uploadSetting['file_types'][$fileType]['upload_max_filesize'];
            } else {
                $this->error('上传文件类型配置错误！');
            }


            View::share('filetype', $arrData["filetype"]);
            View::share('extensions', $extensions);
            View::share('upload_max_filesize', $fileTypeUploadMaxFileSize * 1024);
            View::share('upload_max_filesize_mb', intval($fileTypeUploadMaxFileSize / 1024));
            $maxFiles = intval($uploadSetting['max_files']);
            $maxFiles = empty($maxFiles) ? 20 : $maxFiles;
            $chunkSize = intval($uploadSetting['chunk_size']);
            $chunkSize = empty($chunkSize) ? 512 : $chunkSize;
            View::share('max_files', $arrData["multi"] ? $maxFiles : 1);
            View::share('chunk_size', $chunkSize); //// 单位KB
            View::share('multi', $arrData["multi"]);
            View::share('app', $arrData["app"]);

            $content = hook_one('fetch_upload_view');

            $tabs = ['local', 'url', 'cloud'];

            $tab = !empty($arrData['tab']) && in_array($arrData['tab'], $tabs) ? $arrData['tab'] : 'local';

            if (!empty($content)) {
                $this->assign('has_cloud_storage', true);
            }

            if (!empty($content) && $tab == 'cloud') {
                return $content;
            }

            $tab = $tab == 'cloud' ? 'local' : $tab;

            $this->assign('tab', $tab);
            return $this->fetch(":webuploader");

        }
    }

}
