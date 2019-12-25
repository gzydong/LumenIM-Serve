<?php
namespace App\Helpers;

/**
 * 多图片合成头像
 * Class ImageCompose
 * @package App\Helpers
 * @link https://blog.csdn.net/dongqinliuzi/article/details/48273185
 */
class ImageCompose
{
    //合并图片
    private $images = [];

    // 需要换行的位置
    private $lineArr = [];
    private $space_x = 3;
    private $space_y = 3;
    private $line_x = 0;

    //合并图片大小
    private $pic_w = 0;
    private $pic_h = 0;

    //画布
    private $background;

    /**
     *
     * ImageCompose constructor.
     * @param array $imgs 图片
     */
    public function __construct(array $imgs)
    {
        $this->images = $imgs;
    }

    /**
     * 保存文件
     *
     * @param string $imagePath
     * @return bool|string
     */
    public function saveImage(string $imagePath)
    {
        $res = imagejpeg($this->background, $imagePath);
        if (false === $res) {
            return false;
        }

        // 释放内存
        imagedestroy($this->background);

        return $imagePath;
    }

    /**
     * 渲染输出图片
     */
    public function renderImage()
    {
        header("Content-type: image/jpg");
        imagejpeg($this->background);
        die;
    }

    /**
     * 根据图片数组  拼接成九宫格式拼图
     * @param int $bg_w 背景图片宽度
     * @param int $bg_h 背景图片高度
     */
    function compose($bg_w = 200, $bg_h = 220)
    {
        $pic_list = array_slice($this->images, 0, 9); // 只操作前9个图片

        $this->background = imagecreatetruecolor($bg_w, $bg_h); // 背景图片

        $color = imagecolorallocate($this->background, 202, 201, 201);
        imagefill($this->background, 0, 0, $color);           //区域填充
        imageColorTransparent($this->background, $color);            // 将某个颜色定义为透明色

        $start_x = $start_y = 0;
        switch (count($pic_list)) {
            case 1: // 正中间
                $start_x = intval($bg_w / 4);  // 开始位置X
                $start_y = intval($bg_h / 4);  // 开始位置Y
                $this->pic_w = intval($bg_w / 2); // 宽度
                $this->pic_h = intval($bg_h / 2); // 高度
                break;
            case 2: // 中间位置并排
                $start_x = 2;
                $start_y = intval($bg_h / 4) + 3;
                $this->pic_w = intval($bg_w / 2) - 5;
                $this->pic_h = intval($bg_h / 2) - 5;
                $this->space_x = 5;
                break;
            case 3:
                $start_x = 70;   // 开始位置X
                $start_y = 5;    // 开始位置Y
                $this->pic_w = intval($bg_w / 2) - 5; // 宽度
                $this->pic_h = intval($bg_h / 2) - 5; // 高度
                $this->lineArr = [2];
                $this->line_x = 4;
                break;
            case 4:
                $start_x = 4;    // 开始位置X
                $start_y = 5;    // 开始位置Y
                $this->pic_w = intval($bg_w / 2) - 5; // 宽度
                $this->pic_h = intval($bg_h / 2) - 5; // 高度
                $this->lineArr = [3];
                $this->line_x = 4;
                break;
            case 5:
                $start_x = 50;   // 开始位置X
                $start_y = 50;   // 开始位置Y
                $this->pic_w = intval($bg_w / 3) - 5; // 宽度
                $this->pic_h = intval($bg_h / 3) - 5; // 高度
                $this->lineArr = [3];
                $this->line_x = 5;
                break;
            case 6:
                $start_x = 5;    // 开始位置X
                $start_y = 50;   // 开始位置Y
                $this->pic_w = intval($bg_w / 3) - 5; // 宽度
                $this->pic_h = intval($bg_h / 3) - 5; // 高度
                $this->lineArr = [4];
                $this->line_x = 5;
                break;
            case 7:
                $start_x = 87;   // 开始位置X
                $start_y = 5;    // 开始位置Y
                $this->pic_w = intval($bg_w / 3) - 5; // 宽度
                $this->pic_h = intval($bg_h / 3) - 5; // 高度
                $this->lineArr = [2, 5];
                $this->line_x = 5;
                break;
            case 8:
                $start_x = 45;   // 开始位置X
                $start_y = 5;    // 开始位置Y
                $this->pic_w = intval($bg_w / 3) - 5; // 宽度
                $this->pic_h = intval($bg_h / 3) - 5; // 高度
                $this->lineArr = [3, 6];
                $this->line_x = 5;
                break;
            case 9:
                $start_x = 5;    // 开始位置X
                $start_y = 5;    // 开始位置Y
                $this->pic_w = intval($bg_w / 3) - 5; // 宽度
                $this->pic_h = intval($bg_h / 3) - 5; // 高度
                $this->lineArr = [4, 7];
                $this->line_x = 5;
                break;
        }

        foreach ($pic_list as $k => $pic_path) {
            $kk = $k + 1;
            if (in_array($kk, $this->lineArr)) {
                $start_x = $this->line_x;
                $start_y = $start_y + $this->pic_h + $this->space_y;
            }

            $resource = imagecreatefromjpeg($pic_path);
            imagecopyresized($this->background, $resource, $start_x, $start_y, 0, 0, $this->pic_w, $this->pic_h, imagesx($resource), imagesy($resource));
            $start_x = $start_x + $this->pic_w + $this->space_x;
        }
    }
}
