<?php

namespace FastDog\Content\Events\Category;


/**
 * Список категорий
 *
 * @package FastDog\Content\Events\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentListPrepare
{
    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * ContentListPrepare constructor.
     * @param $data
     */
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
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
