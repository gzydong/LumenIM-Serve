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

    return preg_match('#^1[3,4,5,6,7,8,9]{1}[\d]{9}$#', $mobile) ? true : false;
}