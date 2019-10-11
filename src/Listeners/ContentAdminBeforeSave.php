<?php

namespace FastDog\Content\Listeners;

use App\Core\Notifications;
use App\Modules\Config\Entity\DomainManager;
use FastDog\Content\Entity\Content;
use FastDog\Content\Entity\ContentConfig;
use FastDog\Content\Events\ContentAdminBeforeSave as EventContentAdminBeforeSave;
use App\Modules\Menu\Entity\Menu;
use App\Modules\Users\Entity\User;
use Illuminate\Http\Request;

/**
 * Перед сохранением материала
 *
 * Проверка и исправление маршрутов, генерация метаданных страницы, преобразование абсолютных адресов в относительные в
 * тексте публикации
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentAdminBeforeSave
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * ContentAdminBeforeSave constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param EventContentAdminBeforeSave $event
     * @return void
     */
    public function handle(EventContentAdminBeforeSave $event)
    {
        //$moduleManager = \App::make(ModuleManager::class);
        $data = $event->getData();
        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $config = null;
        $data = \FastDog\Content\Content::prepareTextBeforeSave($data);

        /**
         * @var $user User
         */
        $user = \Auth::getUser();

        $id = $this->request->input('id', null);
        if ($id > 0) {
            /**
             * @var  $item Content
             */
            $item = Content::find($id);
            if ($item) {
                /**
                 * Проверка существования пункта меню с старым псевдонимом в маршруте компонента и
                 * исправление маршрута
                 */
                if ($item->{Content::ALIAS} <> $data[Content::ALIAS]) {
                    $menuItems = Menu::findMenuItem($item->{Content::ALIAS});
                    /**
                     * @var $menuItem Menu
                     */
                    foreach ($menuItems as $menuItem) {
                        $_data = $menuItem->getData();
                        if (isset($_data['data']->type)) {
                            if ($_data['data']->type == 'content_item') {
                                $routeExp = explode('/', $_data['route']);
                                $menuItem->fixRoute($data[Content::ALIAS], $item->{Content::ALIAS}, count($routeExp) - 1);
                                if ($_data['route'] !== $menuItem->getRoute()) {
                                    $this->request->merge([
                                        'update_canonical' => 'Y',
                                    ]);
                                    Menu::where('id', $menuItem->id)->update([
                                        Menu::ROUTE => $menuItem->getRoute(),
                                    ]);
                                    $menuItem = Menu::find($menuItem->id);
                                    Notifications::add([
                                        Notifications::TYPE => Notifications::TYPE_CHANGE_ROUTE,
                                        'message' => 'При изменение псевдонима материала <a href="/{ADMIN}/#!/content/item/' . $item->id .
                                            '" target="_blank">#' . $item->id . '</a> обновлен маршрут меню ' .
                                            '<a href="/{ADMIN}/#!/menu/' . $menuItem->id . '" target="_blank">#' .
                                            $menuItem->id . '</a>',
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $item = new Content();
        }
        /**
         * @var $config ContentConfig
         */
        $config = $item->getPublicConfig();

        if (is_string($data['data'])) {
            $data['data'] = json_decode($data['data']);
        }

        if (isset($data['data']->{Content::CATEGORY_ID})) {
            $data['data']->{Content::CATEGORY_ID} = (int)$data['data']->{Content::CATEGORY_ID};
        }

        if (isset($data['data']->{'meta_title'}) && $data['data']->{'meta_title'} == '') {
            // $data['data']->{'meta_title'} = $data['data']->{'meta_title'};
            $data['data']->{'meta_title'} = $data[Content::NAME];
        }

        /**
         * Генерировать MetaKeyword из текста материалов
         */
        if (!isset($data['data']->{'meta_keywords'})) {
            $data['data']->{'meta_keywords'} = '';
        }
        if ($config !== null && $config->can('generate_keywords') && $data['data']->{'meta_keywords'} == '') {
            $countWord = 20;
            $text = strip_tags($data[Content::INTROTEXT]) . strip_tags($data[Content::FULLTEXT]);
            if (strlen($text)) {
                $topWord = Content::top_words($text, $countWord);
                $data['data']->{'meta_keywords'} = implode(', ', $topWord);
            }
        }


        /**
         * Генерировать Meta Description из текста материалов
         */
        if (!isset($data['data']->{'meta_description'})) {
            $data['data']->{'meta_description'} = '';
        }
        if ($config !== null && $config->can('generate_description') && $data['data']->{'meta_description'} == '') {
            $text = strip_tags($data[Content::INTROTEXT]);
            if (strlen($text)) {
                $data['data']->{'meta_description'} = trim(str_limit($text, 200));
            }
        }

        /**
         * Генерировать теги из текста материалов
         */
        if (!isset($data['data']->{'meta_search_keywords'})) {
            $data['data']->{'meta_search_keywords'} = '';
        }

        if ($config !== null && $config->can('generate_tags') && $data['data']->{'meta_search_keywords'} == '') {
            $countWord = 5;
            $text = strip_tags($data[Content::INTROTEXT]) . strip_tags($data[Content::FULLTEXT]);
            if (strlen($text)) {
                $topWord = Content::top_words($text, $countWord);
                $data['data']->{'meta_search_keywords'} = implode(', ', $topWord);
            }
        }
        /**
         * Преобразование абсолютных адресов в относительные в тексте публикации
         */
        if ($config !== null && $config->can('relative_path')) {
            $domainList = DomainManager::getAccessDomainList();
            $domainList = DomainManager::getAccessDomainList();
            foreach ($domainList as $item) {
                if ($item['id'] == DomainManager::getSiteId()) {
                    $baseDomain = $item[DomainManager::URL];
                $data[Content::INTROTEXT] = str_replace($baseDomain, '', $data[Content::INTROTEXT]);
                $data[Content::FULLTEXT] = str_replace($baseDomain, '', $data[Content::FULLTEXT]);
            }
        }
        }

        $data['data']->canonical = $this->request->input('canonical');

        if (!is_string($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }
        $event->setData($data);
    }
}
