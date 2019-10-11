<?php

namespace FastDog\Content\Events\Category;


use App\Core\Interfaces\AdminPrepareEventInterface;
use FastDog\Content\Entity\ContentCategory;
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

}
