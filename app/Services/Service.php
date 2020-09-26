<?php

namespace App\Services;

use Illuminate\Container\Container;

/**
 * 服务处理层
 *
 * Class Service
 * @package App\Services
 */
class Service
{

    /**
     * 服务列表
     *
     * @var array
     */
    public $childService = [

    ];

    /**
     * @param $attr
     * @return mixed
     */
    public function __get($attr)
    {
        if (!isset($this->childService[$attr])) {
            throw new \InvalidArgumentException('Child Service [' . $attr . '] is not find in ' . get_called_class() . ', you must config it! ');
        }

        if (!Container::getInstance()->has($this->childService[$attr])) {
            $className = $this->childService[$attr];

            Container::getInstance()->singleton($className, function () use ($className) {
                return new $className();
            });
        }

        return Container::getInstance()->make($this->childService[$attr]);
    }
}
