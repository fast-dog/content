<?php

namespace FastDog\Content\Events\Category;


use FastDog\Content\Models\ContentCategory;
use Illuminate\Queue\SerializesModels;

/**
 * Подробная информация о категории в публичном разделе
 *
 * @package FastDog\Content\Events\Category
 */
class ContentCategoryPrepare
{
    use  SerializesModels;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var ContentCategory
     */
    protected $item;

    /***
     * ContentCategoryAdminPrepare constructor.
     * @param array $data
     * @param ContentCategory $item
     */
    public function __construct(array &$data, ContentCategory &$item)
    {
        $this->data = &$data;
        $this->item = &$item;
    }

    /**
     * @return ContentCategory
     */
    public function getItem()
    {
        return $this->item;
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
