<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 18.01.2017
 * Time: 14:33
 */

namespace FastDog\Content\Models\Desktop;


use App\Core\Interfaces\DesktopWidget;
use FastDog\Content\Models\Content;
use FastDog\Content\Events\ContentAdminListPrepare;

/**
 * Блок материалов
 *
 * Блок популярных материалов на главной странице в разделе администрирования
 *
 * @package FastDog\Content\Models\Desktop
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ViewPopularList implements DesktopWidget
{

    /**
     * Параметры
     *
     * @var array|object $config
     */
    protected $config;

    /**
     * Возвращает набор данных для отображения в блоке
     *
     * @return mixed
     */
    public function getData()
    {
        $result = [
            'cols' => [
                [
                    'name' => 'Название',
                    'key' => Content::NAME,
                    'domain' => true,
                ], [
                    'name' => 'Просмотров',
                    'key' => Content::VIEW_COUNTER,
                    'width' => 100,
                    'class' => 'text-center',
                ],
            ],
            'items' => [],
        ];
        $items = Content::orderBy(Content::VIEW_COUNTER, 'DESC')->limit(5)->get();

        /**
         * @var $item Content
         */
        foreach ($items as $item) {
            array_push($result['items'], [
                'id' => $item->id,
                Content::NAME => str_limit($item->{Content::NAME}, 30),
                Content::VIEW_COUNTER => $item->{Content::VIEW_COUNTER},
                Content::SITE_ID => $item->{Content::SITE_ID},
            ]);
        }

        \Event::fire(new ContentAdminListPrepare($result));

        return $result;
    }

    /**
     * Устанавливает набор данных в контексте объекта
     *
     * @param array $data
     * @return mixed
     */
    public function setData(array $data)
    {
        $this->config = $data;
    }
}