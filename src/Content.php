<?php namespace FastDog\Content;


use DOMDocument;
use FastDog\Config\Models\Translate;
use FastDog\Content\Models\Content as ContentModel;
use FastDog\Content\Models\ContentCategory;
use FastDog\Content\Http\Controllers\Site\ContentController;
use FastDog\Core\Models\Cache;
use FastDog\Core\Models\Components;
use FastDog\Core\Models\DomainManager;
use FastDog\Media\Models\Gallery;
use FastDog\Menu\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * Материалы
 *
 * Управление материалами на сайте, создание категорий, статей
 * <ul>
 *      <li>Добавление\Редактрование Материалов</li>
 *      <li>Добавление\Редактрование Категорий</li>
 *      <li>Заполнение SEO данных</li>
 *      <li>Произвольные параметры объекта</li>
 *      <li>Поддержка медиа данных в оформлени</li>
 *      <li>Разграничение доступа по доменной принадлежности (multi site)</li>
 *      <li>Разграничение доступа по ролям в системе ACL</li>
 *      <li>Автоматическое изменение размера изображений в текстовой части материалов</li>
 *      <li>Автоматическая обертка для инициализации раличных галерей</li>
 *      <li>Автоматическая генерация метатегов, тегов поиска</li>
 *      <li>Автоматическое создание поискового индекса для реализации поиска по корню слова</li>
 *      <li>Автоматическое создание канонических ссылок при:
 *           <ul>
 *               <li>изменение псевдонима материала</li>
 *               <li>изменение псевдонима категории</li>
 *               <li>изменение псевдонима пункта меню с типами: Материалы :: Страница материала детально, Материалы :: Блог категории</li>
 *               <li>изменение страницы привязки в меню с типом: Материалы :: Страница материала детально</li>
 *           </ul>
 *      </li>
 *      <li>Добавлены параметры по умолчанию</li>
 *      <li>Добавлен раздел Диагностика, возможности по проверки доступности материалов по каноническим ссылкам</li>
 *      <li>Добавлена загрузка форм из маркера размещенного в тексте публикации</li>
 *</ul>
 *
 * @package FastDog\Content
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class Content extends ContentModel
{
    /**
     * Идентификатор модуля
     * @const string
     */
    const MODULE_ID = 'content';

    /**
     * Тип меню: Материалы категории
     * @const string
     */
    const TYPE_CONTENT_BLOG = 'content_blog';
    /**
     * Тип меню: Отдельная публикация
     * @const string
     */
    const TYPE_CONTENT = 'content_item';

    /**
     * Параметры конфигурации описанные в module.json
     *
     * @var null|object $config
     */
    protected $config;

    /**
     * Возвращает возможные состояния материалов
     *
     * @return array
     */
    public static function getStatusList(): array
    {
        return [
            ['id' => Content::STATE_PUBLISHED, 'name' => trans('content::interface.state_list.published')],
            ['id' => Content::STATE_NOT_PUBLISHED, 'name' => trans('content::interface.state_list.not_published')],
            ['id' => Content::STATE_IN_TRASH, 'name' => trans('content::interface.state_list.in_trash')],
        ];
    }

    /**
     * Возвращает доступные шаблоны
     *
     * @param string $paths
     * @param bool $skip_load_raw
     * @return array
     */
    public function getTemplates($paths = ''): array
    {
        $result = [];

        //получаем доступные пользователю site_id
        $domainsCode = DomainManager::getScopeIds();

        $list = DomainManager::getAccessDomainList();
        foreach ($domainsCode as $code) {
            $_code = $code;
            $currentPath = str_replace('modules', 'public/' . $code . '/modules', $paths);
            if (isset($list[$code])) {
                $code = $list[$code]['name'];
            }
            if ($currentPath !== '') {
                $description = [];
                if (file_exists(dirname($currentPath) . '/.description.php') && $description == []) {
                    $description = include dirname($currentPath) . '/.description.php';
                }
                foreach (glob($currentPath) as $filename) {
                    if (!isset($result[$code])) {
                        $result[$code]['templates'] = [];
                    }
                    $tmp = explode('/', $filename);

                    $count = count($tmp);
                    if ($count >= 2) {
                        $search = array_search($_code, $tmp);
                        if ($search) {
                            $tmp = array_slice($tmp, $search + 1, $count);
                        }
                        $templateName = implode('.', $tmp);

                        $templateName = str_replace(['.blade.php'], [''], $templateName);
                        $name = Arr::last(explode('.', $templateName));

                        if (isset($description[$name])) {
                            $name = $description[$name];
                        }
                        $id = 'theme#' . $_code . '::' . $templateName;
                        $trans_key = str_replace(['.', '::'], '/', $id);

                        array_push($result[$code]['templates'], [
                            'id' => $id,
                            'name' => $name,
                            'translate' => Translate::getSegmentAdmin($trans_key),
                            'raw' => File::get(view($id)->getPath()),
                        ]);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает доступные типы меню
     *
     * @return null|array
     */
    public function getMenuType(): array
    {
        return [
            ['id' => 'content_item', 'name' => trans('content::menu.content_item'), 'sort' => 300],
            ['id' => 'content_item', 'name' => trans('content::menu.content_blog'), 'sort' => 310],
        ];
    }

    /**
     * @return array
     */
    public function getTemplatesPaths(): array
    {
        return [
            'content_item' => "/modules/content/item/*.blade.php",
            'content_blog' => "/modules/content/blog/*.blade.php",
        ];
    }

    /**
     * Возвращает информацию о модуле
     *
     * @param bool $includeTemplates
     * @return array|null
     */
    public function getModuleInfo(): array
    {
        $paths = Arr::first(config('view.paths'));
        $templates_paths = $this->getTemplatesPaths();

        return [
            'id' => self::MODULE_ID,
            'menu' => function () use ($paths, $templates_paths) {
                $result = collect();
                foreach ($this->getMenuType() as $id => $item) {
                    $result->push([
                        'id' => self::MODULE_ID . '::' . $item['id'],
                        'name' => $item['name'],
                        'sort' => $item['sort'],
                        'templates' => (isset($templates_paths[$id])) ? $this->getTemplates($paths . $templates_paths[$id]) : [],
                        'class' => __CLASS__,
                    ]);
                }
                $result = $result->sortBy('sort');

                return $result;
            },
            'templates_paths' => $templates_paths,
            'module_type' => $this->getMenuType(),
            'admin_menu' => function () {
                return $this->getAdminMenuItems();
            },
            'access' => function () {
                return [
                    '000',
                ];
            },
            'route' => function (Request $request, $item) {
                return $this->getMenuRoute($request, $item);
            },
        ];
    }

    /**
     * Устанавливает параметры в контексте объекта
     *
     * @param $data
     * @return mixed
     * @deprecated
     */
    public function setConfig($data)
    {
        $this->config = $data;
    }

    /**
     *  Возвращает параметры объекта
     *
     * @return mixed
     * @deprecated
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Возвращает возможные типы модулей
     *
     * @return mixed
     */
    public function getModuleType()
    {
        $paths = array_first(\Config::get('view.paths'));

        $result = [
            'id' => 'content',
            'instance' => __CLASS__,
            'name' => trans('content::interface.Материалы'),
            'items' => [
                [
                    'id' => 'category',
                    'name' => trans('content::menu.content_blog'),
                    'templates' => $this->getTemplates($paths . '/modules/content/blog/*.blade.php'),
                ],
                [
                    'id' => 'item',
                    'name' => trans('content::menu.content_item'),
                    'templates' => $this->getTemplates($paths . '/modules/content/item/*.blade.php'),
                ],
                [
                    'id' => 'tags',
                    'name' => trans('content::menu.content_tags'),
                    'templates' => $this->getTemplates($paths . '/modules/content/tags/*.blade.php'),
                ],
                [
                    'id' => 'related',
                    'name' => trans('content::menu.content_related'),
                    'templates' => $this->getTemplates($paths . '/modules/content/related/*.blade.php'),
                ],
            ],
        ];

        return $result;
    }


    /**
     * Возвращает маршрут компонента
     *
     * @param Request $request
     * @param Menu $item
     * @return mixed
     */
    public function getMenuRoute($request, $item)
    {
        $result = [];
        /** $parents = $item->getAncestors();
         * @var $parent Menu
         */
        $parent = $item->parent()->first();
        if ($parent && $parent->getParameterByFilterData(['name' => 'EXCLUDE_BREADCRUMBS'], 'N') == 'N') {
            array_push($result, $parent->getRoute());
        }
        if (!in_array($request->input('alias'), ['#', '/', ''])) {
            array_push($result, $request->input('alias'));
        }
        $type = $request->input('type');
        if ($type == '') {
            $request->merge([
                'type' => $request->input('data.type'),
            ]);
        }
        switch ($request->input('type.id')) {
            case self::TYPE_CONTENT_BLOG:
                $id = $request->input('data.category_id', 0);
                if ($id > 0) {
                    /**
                     * @var $category ContentCategory
                     */
                    $category = ContentCategory::where('id', $id)->first();
                    if ($category) {
                        if (!in_array($category->alias, ['#', '/', ''])) {
                            array_push($result, $category->alias);
                        }
                        $data = $category->getData();
                        if ($data['data'] instanceof \StdClass) {
                            $data['data']->menu_id = $item->id;
                        }
                        ContentCategory::where('id', $category->id)->update([
                            ContentCategory::DATA => json_encode($data['data']),
                        ]);
                    }
                }
                break;
            case self::TYPE_CONTENT:
                $page_id = $request->input('route_instance.id', 0);
                if ($page_id > 0) {
                    /** @var Content $page */
                    $page = Content::find($page_id);
                    if ($page) {
                        array_push($result, $page->id . '-' . $page->{self::ALIAS} . '.html');

                    }
                }
                break;
        }


        return [
            'instance' => ContentController::class,
            'route' => implode('/', $result),
        ];
    }


    /**
     * Метод возвращает отображаемый в публичной части контнет
     *
     * @param Components $module
     * @return null|string
     * @throws \Throwable
     */
    public function getContent(Components $module)
    {
        $result = '';
        $data = $module->getData();

        /** @var Cache $cache */
        $cache = app()->make(Cache::class);

        if (isset($data['data']->type->id)) {
            switch ($data['data']->type->id) {
                case 'content::item':
                    if (isset($data['data']->item_id->id)) {
                        $item = Content::where([
                            'id' => $data['data']->item_id->id,
                            Content::STATE => Content::STATE_PUBLISHED,
                        ])->first();

                        if ($item && isset($data['data']->template->id) && view()->exists($data['data']->template->id)) {
                            return view($data['data']->template->id, [
                                'item' => $item,
                            ])->render();
                        }
                    }
                    break;
                case 'content::tags':

                    /** @var Collection $items */
                    $items = $cache->get(__METHOD__ . '::' . DomainManager::getSiteId() . '::tags', function () {
                        return \DB::table(\DB::raw('content_tag ct'))
                            ->select(\DB::raw('ct.text, COUNT(*) as count'))
                            ->groupBy('ct.text')
                            ->orderBy('count', 'desc')
                            ->limit(10)
                            ->get();
                    }, ['content']);


                    if (isset($data['data']->template->id) && view()->exists($data['data']->template->id)) {
                        return view($data['data']->template->id, [
                            'items' => $items,
                        ])->render();
                    }
                    break;
                case 'content::related':// Похожие материалы
                    /** @var array $items */
                    $items = $cache->get(__METHOD__ . '::' . DomainManager::getSiteId() . '::related',
                        function () use ($module) {
                            $result = [];
                            Content::where(function (Builder $query) {
                                $filter = \Request::input('filter', []);
                                if ($filter) {
                                    foreach ($filter as $key => $value) {
                                        switch ($key) {
                                            case 'category_id':
                                                $query->where(Content::CATEGORY_ID, $value);
                                                break;
                                            case 'exclude':
                                                $query->whereNotIn('id', (array)$value);
                                                break;
                                        }
                                    }
                                }
                                $query->where(Content::STATE, Content::STATE_PUBLISHED);
                                $query->where(Content::SITE_ID, DomainManager::getSiteId());
                            })
                                ->limit($module->getParameterByFilterData(['name' => 'LIMIT'], 5))
                                ->orderBy('published_at', 'desc')
                                ->get()
                                ->each(function (Content $contentItem) use ($module, &$result) {
                                    $contentItemData = $contentItem->getData();
                                    //todo: переработать тут позже :=)
                                    $contentItemData[Content::DATA]->media = $contentItem->getMedia();

                                    if (isset($contentItemData[Content::DATA]->media)) {
                                        $contentItem->preview = array_filter($contentItemData[Content::DATA]->media,
                                            function ($image) use ($contentItem, $module) {
                                                if ($image->type == 'file' && $image->src !== '') {
                                                    $defaultWidth = $module->getParameterByFilterData(['name' => 'DEFAULT_WIDTH'], 50);
                                                    $defaultHeight = $module->getParameterByFilterData(['name' => 'DEFAULT_HEIGHT'], 50);
                                                    /**
                                                     * Изменение размеров
                                                     */
                                                    if ($module->getParameterByFilterData(['name' => 'ALLOW_AUTO_RESIZE_IMAGE'], 'N') === 'Y') {
                                                        $src = str_replace(url('/'), '', $image->src);
                                                        $result = Gallery::getPhotoThumb($src, $defaultWidth, $defaultHeight);
                                                        if ($result['exist']) {
                                                            $image->_src = $image->src;
                                                            $image->src = url($result['file']);
                                                        }
                                                    }

                                                    return $image;
                                                }
                                            });
                                    }

                                    array_push($result, $contentItem);
                                });

                            return $result;
                        }, ['content']);


                    if (isset($data['data']->template->id) && view()->exists($data['data']->template->id)) {
                        return view($data['data']->template->id, [
                            'items' => $items,
                        ])->render();
                    }
                    break;
                case 'content::category':

                    $items = Content::where(function (Builder $query) use ($data) {
                        $query->where(Content::STATE, Content::STATE_PUBLISHED);
                        $query->whereIn(Content::SITE_ID, DomainManager::getScopeIds(true));
                        $query->where(Content::CATEGORY_ID, $data['data']->item_id);
                    })->orderBy('created_at', 'desc')
                        ->limit($module->getParameterByFilterData(['name' => 'PAGE_SIZE'], 10))->get();

                    if (isset($data['data']->template->id) && view()->exists($data['data']->template->id)) {
                        return view($data['data']->template->id, [
                            'items' => $items,
                            'module' => $module,
                        ])->render();
                    }
                    break;
                default:

                    break;
            }
        }

        return $result;
    }

    /**
     * Метод удаляет не нужную разметку из текста перед сохранением объектов
     *
     * @param $data
     * @return mixed
     * @deprecated
     */
    public static function prepareTextBeforeSave($data)
    {
        $textFields = [Content::FULLTEXT, Content::INTROTEXT];

        foreach ($textFields as $name) {
            $updatedDom = false;
            $domd = new DOMDocument();
            libxml_use_internal_errors(true);
            if (isset($data[$name])) {
                $domd->loadHTML('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $data[$name]);
                libxml_use_internal_errors(false);
                $frames = $domd->getElementsByTagName('iframe');
                if ($frames->length) {
                    /**
                     * @var $frame DOMElement
                     */
                    foreach ($frames as $frame) {
                        /**
                         * @var $parent DOMElement
                         */
                        $parent = $frame->parentNode->parentNode->parentNode;
                        $parent->removeAttribute('style');
                        $updatedDom = true;
                    }
                }
                if ($updatedDom) {
                    $data[$name] = $domd->saveHTML($domd->documentElement);
                    $data[$name] = str_replace(['<html>', '<body>', '</html>', '</body>'], '', $data[$name]);
                }
            }
        }

        return $data;
    }

    /**
     * Возвращает параметры блоков добавляемых на рабочий стол администратора
     *
     * @return array
     */
    public function getDesktopWidget()
    {
        return [];
    }


    /**
     * Меню администратора
     *
     * Возвращает пунты меню для раздела администратора
     *
     * @return array
     */
    public function getAdminMenuItems()
    {
        $result = [
            'icon' => 'fa-newspaper-o',
            'name' => trans('content::interface.Материалы'),
            'route' => '/content',
            'children' => [],
        ];

        array_push($result['children'], [
            'icon' => 'fa-table',
            'name' => trans('content::interface.Управление'),
            'route' => '/content/items',
            'new' => '/content/item/0',
        ]);

        array_push($result['children'], [
            'icon' => 'fa-table',
            'name' => trans('content::interface.Категории'),
            'route' => '/content/category',
            'new' => '/content/category/0',
        ]);

        array_push($result['children'], [
            'icon' => 'fa-power-off',
            'name' => trans('content::interface.Диагностика'),
            'route' => '/content/diagnostic',
        ]);

        array_push($result['children'], [
            'icon' => 'fa-gears',
            'name' => trans('content::interface.Настройки'),
            'route' => '/content/configuration',
        ]);

        return $result;
    }

    /**
     * @param string $path
     * @param string $site_id
     * @param null $category_id
     * @return array
     */
    public static function getCategoryRoutes($path = 'content', $site_id = '000', $category_id = null)
    {
        $result = [];

        Content::where([
            Content::CATEGORY_ID => $category_id,
            Content::SITE_ID => $site_id,
            Content::STATE => Content::STATE_PUBLISHED,
        ])->get()->each(function (Content $item) use (&$result) {
            $url = $item->getUrl();
            array_push($result, $url);
        });

        return $result;
    }

}
