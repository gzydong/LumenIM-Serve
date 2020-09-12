<?php
/**---------------------公共方法扩展库---------------*/

/**
 * 验证手机号是否正确
 * @param $mobile
 * @return bool
 */
function isMobile($mobile)
{
    return (boolean)preg_match('/^[1][3,4,5,6,7,8,9][0-9]{9}$/', $mobile);
}

/**
 * 验证登录密码格式
 * @param $password
 * @return bool
 */
function isPassword($password)
{
    return (boolean)preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{8,16}$/', $password);
}

/**
 * checkNumber() PHP验证字符串是否为一个数字
 * @param   string $num 字符串
 * @return  boolean 验证通过返回 true 没有通过返回 false
 */
function checkNumber($num)
{
    return is_numeric($num) && (preg_match('/^\s*[+-]?\d+\s*$/', $num) || preg_match('/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/', $num));
}

/**
 * 判断0或正整数
 * @param string $int 验证字符串
 * @param bool $isZero 判断是否可为0
 * @return bool
 */
function isInt(string $int, $isZero = false)
{
    $reg = $isZero ? '/^[+]{0,1}(\d+)$/' : '/^[1-9]\d*$/';
    return is_numeric($int) && preg_match($reg, $int);
}

/**
 * 验证用户 ids
 * @param array $ids
 * @return bool
 */
function checkIds(array $ids)
{
    foreach ($ids as $id) {
        if (!checkNumber($id) || $id <= 0) {
            return false;
        }
    }

    return true;
}

/**
 * 二维数组用指定的key值作为二维数组的key
 *
 * @param $key
 * @param $array
 * @return array
 */
function replaceArrayKey($key, $array)
{
    if (empty($array)) return [];

    $arr = [];
    foreach ($array as $value) {
        $arr[$value[$key]] = $value;
    }

    unset($array);
    return $arr;
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

/**
 * 替换文本中的url 为 a标签
 *
 * @param string $str
 * @return null|string|string[]
 */
function replaceUrlToLink(string $str)
{
    $re = '@((https|http)?://([-\w\.]+)+(:\d+)?(/([\w/_\-.#%]*(\?\S+)?)?)?)@';
    return preg_replace_callback($re, function ($matches) {
        return sprintf('<a href="%s" target="_blank">%s</a>', trim($matches[0], '&quot;'), $matches[0]);
    }, $str);
}

/**
 * @param $arr
 * @param array $sort
 * @return array
 */
function customSort($arr, $sort = [])
{
    $tmp1 = [];
    foreach ($arr as $val) {
        $tmp1[$val['id']] = $val;
    }

    foreach ($sort as $k => $v) {
        $sort[$k] = $tmp1[$v];
    }
    return $sort;
}

/**
 * 获取随机字符串
 * @param int $length 长度
 * @param string $type 类型
 * @param int $convert 转换大小写
 * @return string 随机字符串
 */
function random($length = 6, $type = 'string', $convert = 0)
{
    $config = array(
        'number' => '1234567890',
        'letter' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'string' => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'all' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    );

    if (!isset($config[$type]))
        $type = 'string';
    $string = $config[$type];

    $code = '';
    $strlen = strlen($string) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $string{mt_rand(0, $strlen)};
    }
    if (!empty($convert)) {
        $code = ($convert > 0) ? strtoupper($code) : strtolower($code);
    }
    return $code;
}

/**
 * 生成6位字符的短码字符串
 * @param string $string
 * @return string
 */
function shortCode(string $string)
{
    $result = sprintf("%u", crc32($string));
    $show = '';
    while ($result > 0) {
        $s = $result % 62;
        if ($s > 35) {
            $s = chr($s + 61);
        } elseif ($s > 9 && $s <= 35) {
            $s = chr($s + 55);
        }
        $show .= $s;
        $result = floor($result / 62);
    }

    return $show;
}

/**
 * 获取文件url
 * @param string $path 文件相对路径
 * @return string
 */
function getFileUrl(string $path)
{
    return config('config.file_domain', '') . '/' . $path;
}

/**
 * 二维数组排序
 * @param array $array 数组
 * @param string $field 排序字段
 * @param int $sort 排序方式
 * @return array
 */
function arraysSort(array $array, $field, $sort = SORT_DESC)
{
    array_multisort(array_column($array, $field), $sort, $array);
    return $array;
}

/**
 * 随机生成图片名
 * @param string $ext 图片后缀名
 * @param int $width 图片宽度
 * @param int $height 图片高度
 * @return string
 */
function getSaveImgName(string $ext, int $width, int $height)
{
    return uniqid() . random(18) . uniqid() . '_' . $width . 'x' . $height . '.' . $ext;
}

/**
 * 从HTML文本中提取所有图片
 * @param $content
 * @return array
 */
function getTtmlImgs($content)
{
    $pattern = "/<img.*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/";
    preg_match_all($pattern, htmlspecialchars_decode($content), $match);
    $data = [];
    if (!empty($match[1])) {
        foreach ($match[1] as $img) {
            if (!empty($img)) {
                $data[] = $img;
            }
        }
        return $data;
    }

    return $data;
}

/**
 * 获取两个日期相差多少天
 *
 * @param $day1
 * @param $day2
 * @return float|int
 */
function diffDate($day1, $day2)
{
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        [$second1, $second2] = [$second2, $second1];
    }

    return ceil(($second1 - $second2) / 86400);
}

/**
 * 获取请求token
 *
 * @return string
 */
function parseToken()
{
    $token = app('request')->server->get('HTTP_AUTHORIZATION') ?: app('request')->server->get('REDIRECT_HTTP_AUTHORIZATION');
    if (!empty($token)) {
        $token = str_replace('Bearer ', '', $token);
    } else {
        $token = app('request')->get('token', '');
    }

    return $token;
}
