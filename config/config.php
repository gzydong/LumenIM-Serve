<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/9/20
 * Time: 15:11
 */
return [
    //API 接口签名验证设置
    'signature'=>[
        //访问白名单  例如: api/index/get-home-page
        'except'=>[

        ],

        //允许访问的平台
        'platform'=>[
            //键名代表平台名称

            /**小程序签名信息*/
            'wx-small-routine'=>[
                'appid'=>'PCGgzGuM3kdzF3jCjNe2XXYHhrvzF',
                'secretkey'=>'K9ESy3a7YEfk6kPK3dFC'
            ]
        ]
    ]
];