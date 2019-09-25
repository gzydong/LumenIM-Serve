<?php

namespace App\Logic;

/**
 * Class Logic
 * @package App\Logic
 */
abstract class Logic
{

    /**
     * 获取分页总数
     *
     * @param int $total 总记录数
     * @param int $page_size 分页大小
     * @return int
     */
    protected function getPageTotal(int $total, int $page_size)
    {
        return ($total === 0) ? 0 : (int)ceil((int)$total / (int)$page_size);
    }

    /**
     * 包装分页数据
     *
     * @param array $rows 列表数据
     * @param int $total 数据总记录数
     * @param int $page 当前分页
     * @param int $page_size 分页大小
     * @param array $params 额外参数
     * @return array
     */
    protected function packData(array $rows, int $total, int $page, int $page_size, array $params = [])
    {
        return array_merge([
            'rows' => $rows,
            'page' => $page,
            'page_size' => $page_size,
            'page_total' => ($page_size == 0) ? 1 : $this->getPageTotal($total, $page_size),
            'total' => $total,
        ], $params);
    }
}