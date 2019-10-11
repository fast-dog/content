<?php

namespace FastDog\Content\Events;


use Illuminate\Queue\SerializesModels;

/**
 * При промотре списка материалов на сайте
 *
 * @package FastDog\Content\Events
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentListPrepare
{
    use  SerializesModels;

    /**
     * @var array $data
     */
    protected $data = [];


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
