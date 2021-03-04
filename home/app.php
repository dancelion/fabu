<?php
/**
 * Created by PhpStorm.
 * User: dancelion
 * Date: 2019-08-30
 * Time: 10:58
 */

return [
    'app_debug'              => APP_DEBUG,
    // 应用Trace
    'app_trace'              => APP_DEBUG,
    'app_multi_module'       => false,
    'root_namespace'         => ['plugins' => WEB_ROOT . 'plugins/'],
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    'default_controller'     => 'Index',
    'default_action'         => 'index',
];