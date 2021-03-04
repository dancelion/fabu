<?php
/**
 * Created by PhpStorm.
 * User: dancelion
 * Date: 2019-08-29
 * Time: 15:46
 */

namespace app\apps\controller;

use app\apps\model\AppsModel;
use app\apps\model\VersionModel;
use cmf\controller\AdminBaseController;
use think\Exception;
use think\facade\View;

class IndexController extends AdminBaseController
{
    public function index()
    {
        $list = AppsModel::where(['creatorId' => cmf_get_current_admin_id()])
            ->order('id desc')
            ->paginate(15);
        $page = $list->render();
        return $this->fetch('', compact('list', 'page'));
    }

    public function upload()
    {
        $uploadSetting = cmf_get_upload_setting();

        $arrFileTypes = [
            'image' => ['title' => 'Image files', 'extensions' => $uploadSetting['file_types']['image']['extensions']],
            'video' => ['title' => 'Video files', 'extensions' => $uploadSetting['file_types']['video']['extensions']],
            'audio' => ['title' => 'Audio files', 'extensions' => $uploadSetting['file_types']['audio']['extensions']],
            'file' => ['title' => 'Custom files', 'extensions' => $uploadSetting['file_types']['file']['extensions']]
        ];

        $arrData = [
            'filetype'=>'file'
        ];
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
        View::share('max_files',  1);
        View::share('chunk_size', $chunkSize); //// 单位KB
        View::share('multi', 0);
        View::share('app', 'apps');

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
        return $this->fetch("");
    }

    /**
     * @param int $appId
     * @return mixed
     * @throws Exception
     */
    public function versions(int $appId)
    {
        $info = AppsModel::get(['id' => $appId, 'creatorId' => cmf_get_current_admin_id()]);
        if (!$info) throw new Exception('访问地址非法');
        $list = VersionModel::where(['appId' => $appId])->select();
        return $this->fetch('', compact('info', 'list'));
    }

    public function release()
    {
        $id = $this->request->post('id');
        $info = VersionModel::where(['id' => $id, 'uploadId' => cmf_get_current_admin_id()])->find();
        if (!$info) $this->error('非法操作');
        $result = AppsModel::where(['id' => $info['appId']])
            ->update(['releaseVersionId' => $id, 'releaseVersionCode' => $info['versionCode']]);
        if ($result) {
            $this->success('发布成功');
        }
        $this->error('发布失败');
    }

    public function delVersion()
    {
        $id = $this->request->param('id');
        $info = VersionModel::with('app')->where(['id' => $id, 'uploadId' => cmf_get_current_admin_id()])->find();
        if ($info['app']['releaseVersionId'] == $id) {
            $this->error('当前版本已发布不得删除');
        }
        if ($info->delete()) {
            $this->success('删除成功');
        }
        $this->error('删除失败');
    }

    public function delApp()
    {
        $id = $this->request->param('id');
        $info = AppsModel::where(['id' => $id, 'creatorId' => cmf_get_current_admin_id()])->find();
        if (!$info) {
            $this->error('非法操作');
        }
        if ($info->remove()) {
            $this->success('删除成功');
        }
        $this->error('删除失败');
    }

    public function online($id = 0, $status = 1)
    {
        $info = AppsModel::where(['id' => $id, 'creatorId' => cmf_get_current_admin_id()])->find();
        if (!$info) {
            $this->error('非法操作');
        }
        if ($info->save(['status' => $status], ['id' => $id])) {
            $this->success('设置成功');
        }
        $this->error('设置失败');
    }
}