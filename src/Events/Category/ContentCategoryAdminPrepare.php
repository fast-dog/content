<?php

namespace FastDog\Content\Events\Category;


use FastDog\Content\Models\ContentCategory;
use FastDog\Core\Interfaces\AdminPrepareEventInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

/**
 * Подробная информация о категории в разделе Администрирования
 *
 * @package FastDog\Content\Events\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryAdminPrepare implements AdminPrepareEventInterface
{
    use  SerializesModels;

    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * @var ContentCategory $item
     */
    protected $item;

    /**
     * @var $result array
     */
    protected $result;

    /***
     * ContentCategoryAdminPrepare constructor.
     * @param array $data
     * @param ContentCategory $item
     */
    public function __construct(array &$data, ContentCategory &$item, &$result)
    {
        $this->data = &$data;
        $this->item = &$item;
        $this->result = &$result;
    }

    /**
     * @return ContentCategory
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
     */
    public function setData(array $data): void
    {
        $this->data = $data;
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

}
