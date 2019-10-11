<?php

namespace FastDog\Content\Events\Category;


use FastDog\Content\Entity\ContentCategory;

/**
 * После сохранением категории
 *
 * @package FastDog\Content\Events\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryAdminAfterSave
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var ContentCategory
     */
    protected $item;

    /**
     * ContentAdminPrepare constructor.
     * @param $data
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
