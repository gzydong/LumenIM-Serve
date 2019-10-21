<?php
/**
 * 公共方法扩展库
 */


/**
 * 验证手机号是否正确
 * @param $mobile
 * @return bool
 */
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }

    return preg_match('/^[1][3,4,5,7,8][0-9]{9}$/', $mobile) ? true : false;
}


/**
 * 验证登录密码格式
 * @param $password
 * @return bool
 */
function isPassword($password) {
    return preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{8,16}$/', $password) ? true : false;
}

function replaceArrayKey($key,$array){
    if(empty($array)) return [];

    $arr = [];
    foreach ($array as $value){
        $arr[$value['id']] = $value;
    }
    unset($array);
    return $arr;
}

/**
 * checkNumber() PHP验证字符串是否为一个数字
 * @param   string 	$num      	字符串
 * @return  boolean 验证通过返回 true 没有通过返回 false
 */
function checkNumber($num)
{
    // 是否为数字标量
    if (is_numeric($num)) {
        return preg_match('/^\s*[+-]?\d+\s*$/', $num) || preg_match('/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/', $num) ;
    }

    return false;
}


/**
 * 获取目录下的文件信息
 * @param string $path 目录路径
 * @return array 文件信息
 */
function getPathFileName($path)
{
    $arrReturn = [];
    if (is_dir($path)) {
        $resource = opendir($path);
        if ($resource) {
            while (!!($file = readdir($resource))) {
                if (is_file($path . '/' . $file)) {
                    $arrReturn[] = $file;
                }
            }
            closedir($resource);
        }
    }
    return $arrReturn;
}



function checkIds(array $ids){
    foreach ($ids as $id){
        if(!checkNumber($id) || $id <= 0){
            return false;
        }
    }

    unset($ids);
    return true;
}
