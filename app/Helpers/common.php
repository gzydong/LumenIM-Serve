<?php
/**---------------------公共方法扩展库---------------*/

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
 * 验证用户 ids
 * @param array $ids
 * @return bool
 */
function checkIds(array $ids){
    foreach ($ids as $id){
        if(!checkNumber($id) || $id <= 0){
            return false;
        }
    }

    unset($ids);
    return true;
}


/**
 * 二维数组用指定的key值作为二维数组的key
 *
 * @param $key
 * @param $array
 * @return array
 */
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
 * 聊天消息表情替换
 *
 * @param string $text
 * @return mixed
 */
function emojiReplace(string $text){
    $emojiSearch = ['[微笑]', '[撇嘴]', '[色]', '[发呆]', '[得意]', '[流泪]', '[害羞]', '[闭嘴]', '[睡]', '[大哭]',
        '[尴尬]', '[发怒]', '[调皮]', '[呲牙]', '[惊讶]', '难过', '[酷]', '[冷汗]', '[抓狂]', '[吐]', '[偷笑]', '[可爱]',
        '[白眼]', '[傲慢]', '[饥饿]', '困', '[惊恐]', '[流汗]', '[憨笑]', '[大兵]', '[奋斗]', '咒骂', '[疑问]', '[嘘]',
        '[晕]', '[折磨]', '[衰]', '[骷髅]', '[敲打]', '[再见]', '[擦汗]', '[抠鼻]', '[鼓掌]', '[糗大了]', '[坏笑]', '[左哼哼]',
        '[右哼哼]', '[哈欠]', '[鄙视]', '[委屈]', '[快哭了]', '[阴险]', '[亲亲]', '[吓]', '[可怜]', '[菜刀]', '西瓜', '[啤酒]',
        '[篮球]', '[乒乓]', '[咖啡]', '[饭]', '[猪头]', '[玫瑰]', '[凋谢]', '[示爱]', '[爱心]', '[心碎]', '[蛋糕]', '[闪电]', '[炸弹]',
        '[刀]', '[足球]', '[瓢虫]', '[便便]', '[月亮]', '[太阳]', '[礼物]', '[拥抱]', '[强]', '[弱]', '[握手]', '[胜利]', '[抱拳]', '[勾引]',
        '[拳头]', '[差劲]', '[爱你]', '[NO]', '[OK]', '[爱情]', '[飞吻]', '[跳跳]', '[发抖]', '[怄火]', '[转圈]', '[磕头]', '[回头]', '[跳绳]', '[挥手]',
        '[激动]', '[街舞]', '[献吻]', '[左太极]', '[右太极]'
    ];

    $emojiReplace = [
        "[微笑]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/0.gif'>",
        "[撇嘴]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/1.gif'>",
        "[色]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/2.gif'>",
        "[发呆]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/3.gif'>",
        "[得意]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/4.gif'>",
        "[流泪]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/5.gif'>",
        "[害羞]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/6.gif'>",
        "[闭嘴]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/7.gif'>",
        "[睡]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/8.gif'>",
        "[大哭]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/9.gif'>",
        "[尴尬]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/10.gif'>",
        "[发怒]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/11.gif'>",
        "[调皮]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/12.gif'>",
        "[呲牙]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/13.gif'>",
        "[惊讶]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/14.gif'>",
        "[难过]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/15.gif'>",
        "[酷]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/16.gif'>",
        "[冷汗]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/17.gif'>",
        "[抓狂]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/18.gif'>",
        "[吐]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/19.gif'>",
        "[偷笑]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/20.gif'>",
        "[可爱]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/21.gif'>",
        "[白眼]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/22.gif'>",
        "[傲慢]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/23.gif'>",
        "[饥饿]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/24.gif'>",
        "[困]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/25.gif'>",
        "[惊恐]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/26.gif'>",
        "[流汗]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/27.gif'>",
        "[憨笑]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/28.gif'>",
        "[大兵]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/29.gif'>",
        "[奋斗]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/30.gif'>",
        "[咒骂]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/31.gif'>",
        "[疑问]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/32.gif'>",
        "[嘘]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/33.gif'>",
        "[晕]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/34.gif'>",
        "[折磨]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/35.gif'>",
        "[衰]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/36.gif'>",
        "[骷髅]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/37.gif'>",
        "[敲打]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/38.gif'>",
        "[再见]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/39.gif'>",
        "[擦汗]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/40.gif'>",
        "[抠鼻]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/41.gif'>",
        "[鼓掌]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/42.gif'>",
        "[糗大了]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/43.gif'>",
        "[坏笑]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/44.gif'>",
        "[左哼哼]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/45.gif'>",
        "[右哼哼]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/46.gif'>",
        "[哈欠]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/47.gif'>",
        "[鄙视]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/48.gif'>",
        "[委屈]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/49.gif'>",
        "[快哭了]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/50.gif'>",
        "[阴险]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/51.gif'>",
        "[亲亲]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/52.gif'>",
        "[吓]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/53.gif'>",
        "[可怜]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/54.gif'>",
        "[菜刀]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/55.gif'>",
        "[西瓜]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/56.gif'>",
        "[啤酒]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/57.gif'>",
        "[篮球]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/58.gif'>",
        "[乒乓]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/59.gif'>",
        "[咖啡]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/60.gif'>",
        "[饭]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/61.gif'>",
        "[猪头]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/62.gif'>",
        "[玫瑰]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/63.gif'>",
        "[凋谢]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/64.gif'>",
        "[示爱]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/65.gif'>",
        "[爱心]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/66.gif'>",
        "[心碎]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/67.gif'>",
        "[蛋糕]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/68.gif'>",
        "[闪电]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/69.gif'>",
        "[炸弹]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/70.gif'>",
        "[刀]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/71.gif'>",
        "[足球]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/72.gif'>",
        "[瓢虫]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/73.gif'>",
        "[便便]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/74.gif'>",
        "[月亮]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/75.gif'>",
        "[太阳]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/76.gif'>",
        "[礼物]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/77.gif'>",
        "[拥抱]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/78.gif'>",
        "[强]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/79.gif'>",
        "[弱]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/80.gif'>",
        "[握手]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/81.gif'>",
        "[胜利]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/82.gif'>",
        "[抱拳]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/83.gif'>",
        "[勾引]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/84.gif'>",
        "[拳头]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/85.gif'>",
        "[差劲]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/86.gif'>",
        "[爱你]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/87.gif'>",
        "[NO]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/88.gif'>",
        "[OK]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/89.gif'>",
        "[爱情]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/90.gif'>",
        "[飞吻]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/91.gif'>",
        "[跳跳]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/92.gif'>",
        "[发抖]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/93.gif'>",
        "[怄火]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/94.gif'>",
        "[转圈]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/95.gif'>",
        "[磕头]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/96.gif'>",
        "[回头]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/97.gif'>",
        "[跳绳]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/98.gif'>",
        "[挥手]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/99.gif'>",
        "[激动]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/100.gif'>",
        "[街舞]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/101.gif'>",
        "[献吻]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/102.gif'>",
        "[左太极]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/103.gif'>",
        "[右太极]" => "<img src='https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/104.gif'>",
    ];
    $text = str_replace($emojiSearch,array_values($emojiReplace),$text);
    return $text;
}
