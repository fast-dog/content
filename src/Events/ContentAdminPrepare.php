<?php

namespace FastDog\Content\Events;


use App\Core\Interfaces\AdminPrepareEventInterface;
use FastDog\Content\Entity\Content;

/**
 * При редактирование в разделе администрирования
 *
 * @package FastDog\Content\Events
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentAdminPrepare implements AdminPrepareEventInterface
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
     * @var $result array
     */
    protected $result;

    /**
     * ContentAdminPrepare constructor.
     * @param array $data
     * @param Content $item
     * @param $result
     */
    public function __construct(array &$data, Content &$item, &$result)
    {
        $this->data = &$data;
        $this->item = &$item;
        $this->result = &$result;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function setResult($result)
    {
        $this->result = $result;
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
