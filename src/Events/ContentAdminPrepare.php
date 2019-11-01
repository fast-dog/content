<?php

namespace FastDog\Content\Events;


use FastDog\Content\Models\Content;
use FastDog\Core\Interfaces\AdminPrepareEventInterface;
use Illuminate\Database\Eloquent\Model;

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
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function setResult(array $result): void
    {
        $this->result = $result;
    }

    /**
     * @return Content
     */
    public function getItem(): Model
    {
        return $this->item;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
