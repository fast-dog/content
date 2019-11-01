<?php
namespace FastDog\Content\Events;


use Illuminate\Queue\SerializesModels;

/**
 * Обработка элеметнов списка в разделе администратора
 *
 * @package FastDog\Content\Events
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentAdminListPrepare
{
    use  SerializesModels;

    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * ContentAdminListPrepare constructor.
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
