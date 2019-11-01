<?php

namespace FastDog\Content\Listeners\Category;

use FastDog\Content\Events\Category\ContentCategoryAdminBeforeSave as EventContentCategoryAdminBeforeSave;
use FastDog\Content\Models\Content;
use FastDog\Content\Models\ContentCanonical;
use FastDog\Content\Models\ContentCategory;
use FastDog\Core\Models\Notifications;
use FastDog\Menu\Models\Menu;
use FastDog\User\Models\User;
use Illuminate\Http\Request;

/**
 * Перед сохранением категории
 *
 * Исправление маршрутов меню, канонических ссылок у материалов
 *
 * @package FastDog\Content\Listeners\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryAdminBeforeSave
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
     * @param EventContentCategoryAdminBeforeSave $event
     * @return void
     */
    public function handle(EventContentCategoryAdminBeforeSave $event)
    {
        //$moduleManager = \App::make(ModuleManager::class);
        $data = $event->getData();
        /**
         * @var $user User
         */
        $user = \Auth::getUser();

        $id = $this->request->input('id', null);
        if ($id > 0) {
            /**
             * @var  $item ContentCategory
             */
            $item = ContentCategory::find($id);
            if ($item) {
                /**
                 * Проверка существования пункта меню с старым псевдонимом в маршруте компонента
                 */
                if ($item->{ContentCategory::ALIAS} <> $data[ContentCategory::ALIAS]) {

                    if (isset($data['data']->menu_id)) {
                        $menuItems = Menu::where('id', $data['data']->menu_id)->get();
                    } else {
                        $menuItems = Menu::findMenuItem($item->{ContentCategory::ALIAS});
                    }

                    /**
                     * Исправление маршрутов пунктов меню,
                     * канонических ссылок и материалах
                     *
                     * @var $menuItem Menu
                     */
                    foreach ($menuItems as $menuItem) {
                        $_data = $menuItem->getData();
                        if (isset($_data['data']->type->id) && $_data['data']->type->id == 'content_blog') {
                            $routeExp = explode('/', $_data['route']);
                            $menuItem->fixRoute($data[ContentCategory::ALIAS],
                                $item->{ContentCategory::ALIAS}, count($routeExp) - 1);

                            if ($_data['route'] !== $menuItem->getRoute()) {
                                Menu::where('id', $menuItem->id)->update([
                                    Menu::ROUTE => $menuItem->getRoute(),
                                ]);
                                $menuItem = Menu::find($menuItem->id);
                                Notifications::add([
                                    Notifications::TYPE => Notifications::TYPE_CHANGE_ROUTE,
                                    'message' => 'При изменение псевдонима категории материалов <a href="/{ADMIN}/#!/content/category/' . $item->id .
                                        '" target="_blank">#' . $item->id . '</a> обновлен маршрут меню ' .
                                        '<a href="/{ADMIN}/#!/menu/' . $menuItem->id . '" target="_blank">#' . $menuItem->id . '</a>',
                                ]);

                                /**
                                 * Обновляем канонические ссылки у материалов
                                 */
                                $contentItems = Content::where(Content::CATEGORY_ID, $item->id)->get();
                                /**
                                 * @var $contentItem Content
                                 */
                                foreach ($contentItems as $contentItem) {
                                    $result = ContentCanonical::ContentCanonicalCheckCategoryBlog($item,
                                        $contentItem, $contentItem->getCategoryUrl($menuItem, false));
                                    if ($result['create']) {
                                        Notifications::add([
                                            Notifications::TYPE => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                                            'message' => 'При изменение псевдонима категории материалов <a href="/{ADMIN}/#!/content/category/' . $item->id .
                                                '" target="_blank">#' . $item->id . '</a> в материал <a href="/{ADMIN}/#!/content/item/' . $contentItem->id .
                                                '" target="_blank">#' . $contentItem->id . '</a> была добавлена каноническая ссылка.',
                                        ]);
                                    }

                                    if ($result['update']) {
                                        Notifications::add([
                                            Notifications::TYPE => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                                            'message' => 'При изменение псевдонима категории материалов <a href="/{ADMIN}/#!/content/category/' . $item->id .
                                                '" target="_blank">#' . $item->id . '</a> в материал <a href="/{ADMIN}/#!/content/item/' . $contentItem->id .
                                                '" target="_blank">#' . $contentItem->id . '</a> была обновлена каноническая ссылка.',
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (is_string($data['data'])) {
            $data['data'] = json_decode($data['data']);
        }

        if (isset($data['data']->{'meta_title'}) && $data['data']->{'meta_title'} == '') {
            $data['data']->{'meta_title'} = $data[ContentCategory::NAME];
        } else {
            $data['data']->{'meta_title'} = $data[ContentCategory::NAME];
        }
        /**
         * Генерировать MetaKeyword из текста материалов
         */
        if (!isset($data['data']->{'meta_keywords'})) {
            $data['data']->{'meta_keywords'} = '';
        }
        if ($data['data']->{'meta_keywords'} == '') {
            $countWord = 20;
            $text = strip_tags($data['data']->{Content::INTROTEXT}) . strip_tags($data['data']->{Content::FULLTEXT});
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
        if ($data['data']->{'meta_description'} == '') {
            $text = strip_tags($data['data']->{Content::INTROTEXT});
            if (strlen($text)) {
                $data['data']->{'meta_description'} = trim(str_limit($text, 200));
            }
        }

        if (!is_string($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }

        $event->setData($data);
    }
}
