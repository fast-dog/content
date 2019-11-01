<?php

namespace FastDog\Content\Models\Desktop;


use FastDog\Content\Models\ContentStatistic;
use FastDog\Core\Interfaces\DesktopWidget;

/**
 * Блок графика
 *
 * Блок графика в разделе администрирования
 *
 * @package FastDog\Content\Models\Desktop
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ViewGraph implements DesktopWidget
{
    /**
     * Параметры модуля
     *
     * @var array|object $config
     */
    protected $config;

    /**
     * Возвращает набор данных для отображения в блоке
     *
     * @return mixed
     */
    public function getData(): array
    {
        return ContentStatistic::getStatistic();
    }

    /**
     * Устанавливает набор данных в контексте объекта
     *
     * @param array $data
     * @return mixed
     */
    public function setData(array $data): void
    {
        $this->config = $data;
    }
}