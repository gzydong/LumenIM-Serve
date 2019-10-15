<?php
/**
 * 公共方法扩展库
 */


/**
 * 验证手机号是否正确
 *
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
 *
 * @param $mobile
 * @return bool
 */
function isPassword($password) {
    return preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{8,16}$/', $password) ? true : false;
}


