<?php
namespace FastDog\Content\Events;



use FastDog\Content\Models\Content;

/**
 * При просмотре материала на сайте
 *
 * @package FastDog\Content\Events
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentPrepare
{
    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * @var Content $item
     */
    protected $item;

    /**
     * ContentAdminPrepare constructor.
     * @param $data - view data
     * @param Content $item
     */
    public function __construct(array &$data, Content &$item)
    {
        $this->data = &$data;
        $this->item = &$item;
    }

    /**
     * @return Content
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
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
