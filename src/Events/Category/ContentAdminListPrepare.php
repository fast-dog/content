<?php

namespace FastDog\Content\Events\Category;


/**
 * Обрбаотка списка категорий
 *
 * Обрбаотка списка категорий в разделе Администрирования
 *
 * @package FastDog\Content\Events\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 * @deprecated
 */
class ContentAdminListPrepare
{
    /**
     * @var array $data
     */
    protected $data = [];


    public function __construct(&$data)
    {
        $this->data = &$data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
