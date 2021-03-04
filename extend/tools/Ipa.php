<?php


namespace tools;


use CFPropertyList\CFPropertyList;
use Chumper\Zipper\Zipper;
use IosPngParser\Parser as IosPngParser;
use think\Exception;

class Ipa extends PackageResolution
{

    function parse()
    {
        $zipper = new Zipper;
        $zipFiles = $zipper->make($this->file)->listFiles('/app\/Info\.plist$/i');
        if (empty($zipFiles)) {
            throw new Exception('安装包解析失败');
        }
        $filePath = array_pop($zipFiles);

        // 正则匹配包根目录中的Info.plist文件
        if (preg_match("/Payload\/([^\/]*)\/Info\.plist$/i", $filePath, $matches)) {
            $app_folder = $matches[1];
            $to_folder = 'upload/plist/' . $app_folder . '/';
            // 将plist文件解压到ipa目录中的对应包名目录中
            $folder = $zipper->make($this->file)->folder('Payload/' . $app_folder);
            $folder->extractTo($to_folder, ["Info.plist","embedded.mobileprovision"], Zipper::WHITELIST);
            // 翻译embedded.mobileprovision
            $provisionPath = $to_folder . 'embedded.mobileprovision';
            $provisionPlistPath = $to_folder . 'provision.plist';
            shell_exec("openssl smime -inform der -verify -noverify -in {$provisionPath}> {$provisionPlistPath}");
            // 获取plist文件内容
            $plistContent = file_get_contents($to_folder . 'Info.plist');
            $provisionContent = simplexml_load_file($provisionPlistPath);
            // 解析plist成数组
            $ipa = new CFPropertyList();
            $ipa->parse($plistContent);
            $ipaInfo = $ipa->toArray();

            $iconName = array_pop($ipaInfo['CFBundleIcons']['CFBundlePrimaryIcon']['CFBundleIconFiles']) . '@3x.png';
            $folder->extractTo($to_folder, [$iconName], Zipper::WHITELIST);
            $parser = new IosPngParser();
            $parser::fix($to_folder . $iconName, $to_folder . $iconName);
//            echo '<img src="/' . $to_folder . $iconName . '" />';
            $info = [
                'platform' => 'ios',
                'bundleId' => $ipaInfo['CFBundleIdentifier'],
                'bundleName' => $ipaInfo['CFBundleName'],
                'appName' => $ipaInfo['CFBundleDisplayName'],
                'versionStr' => $ipaInfo['CFBundleVersion'],
                'versionCode' => $ipaInfo['CFBundleShortVersionString'],
                'iconName' => $ipaInfo['CFBundleIcons']['CFBundlePrimaryIcon']['CFBundleIconName'],
                'icon' => $to_folder . $iconName,
            ];
            try {
                $environment = $provisionContent->Entitlements['aps-environment'];
                $active = isset($provisionContent->Entitlements['beta-reports-active'])?$provisionContent->Entitlements['beta-reports-active']:false;
                if ($environment == 'production') {
                    $info['appLevel'] = $active ? 'appstore' : 'enterprise';
                } else {
                    $info['appLevel'] = 'develop';
                }
                return $info;
            } catch (\Exception $e) {
                $info['appLevel'] = 'develop';
                throw new Exception('应用未签名,暂不支持');
            }
        }
    }

    public static function mapInstallUrl($appId)
    {
        $url = url("/plist/{$appId}","",false,'app');
        return "itms-services://?action=download-manifest&url={$url}";
    }
}