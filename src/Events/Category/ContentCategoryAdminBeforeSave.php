<?php

namespace FastDog\Content\Events\Category;


/**
 * Перед сохранением категории
 *
 * @package FastDog\Content\Events\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryAdminBeforeSave
{
    /**
     * @var array $data
     */
    protected $data = [];


    /**
     * ContentAdminBeforeSave constructor.
     * @param array $data
     */
    public function __construct(array &$data)
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
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
