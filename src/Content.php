<?php namespace FastDog\Content;


use DOMDocument;
use FastDog\Content\Entity\Content as ContentModel;
use FastDog\Content\Entity\ContentCanonical;
use FastDog\Content\Entity\ContentCanonicalCheckResult;
use FastDog\Content\Entity\ContentCategory;
use FastDog\Content\Entity\ContentComments;
use FastDog\Content\Entity\ContentConfig;
use FastDog\Content\Entity\ContentStatistic;
use FastDog\Content\Entity\ContentTag;
use FastDog\Content\Http\Controllers\Site\ContentController;
use FastDog\Menu\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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
     * Маршруты раздела администратора
     *
     * @return mixed
     */
    public function routeAdmin()
    {
        $this->aclName = __CLASS__ . '::' . DomainManager::getSiteId() . '::guest';

        $baseParameters = ['middleware' => ['acl'], 'is' => DomainManager::getSiteId() . '::admin',];

        /**
         * Таблица
         */
        \Route::post('/public/content/list', array_replace_recursive($baseParameters, [
            'uses' => '\FastDog\Content\Controllers\Admin\ContentTableController@list',
            'can' => 'view.' . $this->aclName,
        ]));

        /**
         * Форма
         */
        $ctrl = '\FastDog\Content\Controllers\Admin\ContentFormController';

        // Отдельный объект
        \Route::get('/public/content/{id}', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getEditItem',
            'can' => 'view.' . $this->aclName,
        ]))->where('id', '[1-90]+');

        // Обновление объекта из списка (публикаций, перемещение в корзинку)
        \Route::post('/public/content/update', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postContentUpdate',
            'can' => 'update.' . $this->aclName,
        ]));

        // Обновление объекта из списка (публикаций, перемещение в корзинку)
        \Route::post('/public/content/list/self-update', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postContentUpdate',
            'can' => 'update.' . $this->aclName,
        ]));

        // Обновлени объекта
        \Route::post('/public/content', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postContent',
            'can' => 'update.' . $this->aclName,
        ]));

        // Добавление материала
        \Route::post('/public/add-content', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postContent',
            'can' => 'update.' . $this->aclName,
        ]));

        // Копирование объекта
        \Route::post('/public/content/replicate', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postReplicate',
            'can' => 'create.' . $this->aclName,
        ]));

        // Удаление значения дополнительного параметра
        \Route::post('/content/delete-select-value', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postDeleteSelectValue',
            'can' => 'api.' . $this->aclName,
        ]));

        // Добавление значения дополнительного параметра
        \Route::post('/content/add-select-value', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postAddSelectValue',
            'can' => 'api.' . $this->aclName,
        ]));

        // Добавление\Сохранение дополнительного параметра
        \Route::post('/content/save-property', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postSaveProperty',
            'can' => 'api.' . $this->aclName,
        ]));

        /**
         * API
         */
        $ctrl = '\FastDog\Content\Controllers\Admin\ApiController';

        \Route::get('/public/content/admin-info', $ctrl . '@getAdminInfo');

        // Очистка кэша
        \Route::post('/public/content/clear-cache', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postClearCache',
            'can' => 'api.' . $this->aclName,
        ]));
        // Изменение разрешений для модуля
        \Route::post('/public/content/access', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postAccess',
            'can' => 'info.' . $this->aclName,
        ]));
        // Сохранение параметров модуля
        \Route::post('/public/content/save-module-configurations', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postSaveModuleConfigurations',
            'can' => 'api.' . $this->aclName,
        ]));

        // Поиск канонических ссылок на материалы
        \Route::post('/public/content/canonicals', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postCanonicals',
            'can' => 'api.' . $this->aclName,
        ]));

        // Страница диагностики
        \Route::get('/public/content/diagnostic', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getDiagnostic',
            'can' => 'api.' . $this->aclName,
        ]));

        // Таблица проверки канонических ссылок на материалы
        \Route::post('/public/content/check-canonical', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postCheckCanonical',
            'can' => 'api.' . $this->aclName,
        ]));

        // Проверка канонических ссылок на материалы
        \Route::get('/public/content/check-canonical', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getCheckCanonical',
            'can' => 'api.' . $this->aclName,
        ]));

        //шаблоны редактора
        \Route::get('/public/content/ck-templates.js', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getCkTemplates',
            'can' => 'api.' . $this->aclName,
        ]));

        //поиск материалов
        \Route::post('/public/content/search-list', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getPageSearch',
            'can' => 'api.' . $this->aclName,
        ]));


        //поиск материалов по названию
//        \Route::get('/public/content/search', array_replace_recursive($baseParameters, [
//            'uses' => $ctrl . '@getSearch',
//            'can' => 'api.' . $this->aclName,
//        ]));

        /**
         * Категории
         */
        // Таблица катагорий
        \Route::post('/public/content/category/list', array_replace_recursive($baseParameters, [
            'uses' => '\FastDog\Content\Controllers\Admin\Category\TableController@list',
            'can' => 'view.category.' . $this->aclName,
        ]));
        /**
         * Форма
         */
        $ctrl = '\FastDog\Content\Controllers\Admin\Category\FormController';

        // Форма редактирования категории
        \Route::get('/public/content/category/{id}', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getEditItem',
            'can' => 'view.category.' . $this->aclName,
        ]));

        // Сохранение категории
        \Route::post('/public/content/category', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postCategory',
            'can' => 'update.category.' . $this->aclName,
        ]));

//        //обновление объекта
        \Route::post('/public/content/category/list/self-update', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postUpdate',
            'can' => 'update.category.' . $this->aclName,
        ]));
//        //создание объекта
//        \Route::post('/public/content/category-create', array_replace_recursive($baseParameters, [
//            'uses' => $ctrl . '@postCategory',
//            'can' => 'create.category.' . $this->aclName,
//        ]));
//
//        //копирование объекта
//        \Route::post('/public/content/category/replicate', array_replace_recursive($baseParameters, [
//            'uses' => $ctrl . '@postReplicate',
//            'can' => 'create.category.' . $this->aclName,
//        ]));
//
//        //обновление объекта из списка
//        \Route::post('/public/content/category/update', array_replace_recursive($baseParameters, [
//            'uses' => $ctrl . '@postUpdate',
//            'can' => 'update.category.' . $this->aclName,
//        ]));

    }

    /**
     * Маршруты публичного раздела
     *
     * @return mixed
     */
    public function routePublic()
    {
        $key = 'content-' . DomainManager::getSiteId();

        $data = (env('CACHE_DRIVER') == 'redis') ? \Cache::tags(['core'])->get($key, null) : \Cache::get($key, null);

        if ($data === null) {
            $menuItems = Menu::where(function (Builder $query) {
                $query->where(Menu::STATE, Menu::STATE_PUBLISHED);
                $query->where(Menu::SITE_ID, DomainManager::getSiteId());
            })->get();
            /**
             * @var $menuItem Menu
             */
            foreach ($menuItems as $menuItem) {
                $_data = $menuItem->getData();
                /**
                 * Формируем список
                 */
                if (isset($_data['data']->type) && $_data['data']->type == 'content_blog') {
                    $data[] = $menuItem;
                }
            }
            if (count($data)) {
                if (env('CACHE_DRIVER') == 'redis') {
                    \Cache::tags(['core'])->put($key, $data, config('cache.content.route', 5));
                } else {
                    \Cache::put($key, $data, config('cache.content.route', 5));
                }
            }
        }

        if ($data) {
            /**
             * @var $datum Menu
             */
            foreach ($data as $menuItem) {
                \Route::get($menuItem->getRoute() . '/{id}-{alias}.html', function (Request $request, $id, $alias, $page = null) use ($menuItem) {
                    $request->merge([
                        'active_ids' => $menuItem->id,
                    ]);
                    /**
                     * @var $contentItem Content
                     */
                    $contentItem = self::where([
                        self::ALIAS => $alias,
                        'id' => $id,
                    ])->first();


                    if (!$contentItem) {
                        $menuItem->error();
                        abort(404);
                    }
                    $controller = app()->make('\FastDog\Content\Controllers\Site\ContentController');

                    return $controller->callAction('getItem', [
                        ['request' => $request, 'menuItem' => $menuItem, 'contentItem' => $contentItem],
                    ]);
                });
            }
        }

        \Route::post('/content/add-comment/{item_id}', '\FastDog\Content\Controllers\Site\ContentController@postAddComment');
    }

    /**
     * События обрабатываемые модулем
     *
     * @return mixed
     */
    public function initEvents()
    {
        return [
            //Обрбаотка данных при выводе в список в админке
            'FastDog\Content\Events\ContentAdminListPrepare' => [
                'FastDog\Content\Listeners\ContentAdminListPrepare',
            ],
            //Обрбаотка данных при выводе в форму редактирования
            'FastDog\Content\Events\ContentAdminPrepare' => [
                'App\Core\Listeners\AdminItemPrepare',// <-- Поля даты обновления и т.д.
                'App\Core\Listeners\MetadataAdminPrepare',// <-- SEO
                'FastDog\Content\Listeners\ContentAdminPrepare',
                'FastDog\Content\Listeners\ContentItemAdminSetEditorForm',//<-- ставим форму редактирования
            ],
            //Обрбаотка данных при выводе в форму редактирования
            'FastDog\Content\Events\Category\ContentCategoryAdminPrepare' => [
                'App\Core\Listeners\AdminItemPrepare',// <-- Поля даты обновления и т.д.
                'App\Core\Listeners\MetadataAdminPrepare',// <-- SEO
                'FastDog\Content\Listeners\Category\ContentCategoryAdminPrepare',
                'FastDog\Content\Listeners\Category\ContentCategoryAdminSetEditorForm',//<-- ставим форму редактирования
            ],
            //Обрбаотка данных при выводе в таблицу администрирования
            'FastDog\Content\Events\Category\ContentAdminListPrepare' => [
                'FastDog\Content\Listeners\Category\ContentAdminListPrepare',
            ],
            //Обрбаотка данных при выводе материала в публичной части
            'FastDog\Content\Events\ContentPrepare' => [
                'FastDog\Content\Listeners\ContentPrepare',
            ],
            //Обрбаотка данных перед сохранением материала в разделе администрирования
            'FastDog\Content\Events\ContentAdminBeforeSave' => [
                'FastDog\Content\Listeners\ContentAdminBeforeSave',
            ],
            //Обрбаотка данных после сохранением материала в разделе администрирования
            'FastDog\Content\Events\ContentAdminAfterSave' => [
                'FastDog\Content\Listeners\ContentAdminAfterSave',
            ],
            //Обрбаотка данных перед сохранением категории в разделе администрирования
            'FastDog\Content\Events\Category\ContentCategoryAdminBeforeSave' => [
                'FastDog\Content\Listeners\Category\ContentCategoryAdminBeforeSave',
            ],
            //Обрбаотка данных после сохранением категории в разделе администрирования
            'FastDog\Content\Events\Category\ContentCategoryAdminAfterSave' => [
                'FastDog\Content\Listeners\Category\ContentCategoryAdminAfterSave',
            ],
            //Обрбаотка данных при выводе списка категорий в публичном разделе
            'FastDog\Content\Events\Category\ContentListPrepare' => [
                'FastDog\Content\Listeners\Category\ContentListPrepare',
            ],
            //Обрбаотка данных при выводе категории в публичном разделе
            'FastDog\Content\Events\Category\ContentCategoryPrepare' => [
                'FastDog\Content\Listeners\Category\ContentCategoryPrepare',
            ],
            //Обрбаотка данных при выводе категории в публичном разделе
            'FastDog\Content\Events\ContentListPrepare' => [
                'FastDog\Content\Listeners\ContentListPrepare',
            ],
            //исправляем канонические ссылки после сохранения пункта меню
            'App\Modules\Menu\Events\MenuItemAfterSave' => [
                'FastDog\Content\Listeners\MenuItemAfterSave',
            ],
            //исправляем канонические ссылки до сохранения пункта меню
            'App\Modules\Menu\Events\MenuItemBeforeSave' => [
                'FastDog\Content\Listeners\MenuItemBeforeSave',
            ],
        ];
    }

    /**
     * Возвращает возможные состояния материалов
     *
     * @return array
     */
    public static function getStatusList(): array
    {
        return [
            ['id' => Content::STATE_PUBLISHED, 'name' => trans('app.Опубликовано')],
            ['id' => self::STATE_NOT_PUBLISHED, 'name' => trans('app.Не опубликовано')],
            ['id' => self::STATE_IN_TRASH, 'name' => trans('app.В корзине')],
        ];
    }

    /**
     * Возвращает доступные шаблоны
     *
     * @param string $paths
     * @param bool $skip_load_raw
     * @return array
     */
    public function getTemplates($paths = '', $skip_load_raw = false)
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
                    $description = include_once dirname($currentPath) . '/.description.php';
                }
                foreach (glob($currentPath) as $filename) {
                    if (!isset($result[$code])) {
                        $result[$code]['templates'] = [];
                    }

                    $tmp = explode('/', $filename);
                    $templateName = array_last($tmp);
                    $count = count($tmp);
                    if ($count >= 2) {
                        $templateType = $tmp[$count - 2];
                        $templateName = str_replace(['.blade.php'], [''], $templateName);
                        $name = $templateName;
                        if (isset($description[$templateName])) {
                            $name = $description[$templateName];
                        }
                        $id = 'theme#' . $_code . '::modules.content.' . $templateType . '.' . $templateName;
                        $trans_key = str_replace(['.', '::'], '/', $id);

                        array_push($result[$code]['templates'], [
                            'id' => $id,
                            'name' => $name,
                            'trans_key' => $trans_key,
                            'translate' => ($skip_load_raw === false) ? Translate::getSegmentAdmin($trans_key) : [],
                            'raw' => ($skip_load_raw === false) ? \File::get(view($id)->getPath()) : [],
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
    public function getMenuType()
    {
        return (isset($this->config->menu)) ? $this->config->menu : [];
    }

    /**
     * @return array
     */
    public function getTemplatesPaths(): array
    {
        return [

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
            'menu' => function() use ($paths, $templates_paths) {
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
            'admin_menu' => function() {
                return $this->getAdminMenuItems();
            },
            'access' => function() {
                return [
                    '000',
                ];
            },
            'route' => function(Request $request, $item) {
                return $this->getMenuRoute($request, $item);
            }
        ];
    }

    /**
     * Устанавливает параметры в контексте объекта
     *
     * @param $data
     * @return mixed
     */
    public function setConfig($data)
    {
        $this->config = $data;
    }

    /**
     *  Возвращает параметры объекта
     *
     * @return mixed
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
            'name' => trans('app.Материалы'),
            'items' => [
                [
                    'id' => 'category',
                    'name' => trans('app.Материалы') . ' :: ' . trans('app.Блог категории'),
                    'templates' => $this->getTemplates($paths . '/modules/content/blog/*.blade.php'),
                ],
                [
                    'id' => 'item',
                    'name' => trans('app.Материалы') . ' :: ' . trans('app.Отдельная публикация'),
                    'templates' => $this->getTemplates($paths . '/modules/content/item/*.blade.php'),
                ],
                [
                    'id' => 'tags',
                    'name' => trans('app.Материалы') . ' :: ' . trans('app.Теги'),
                    'templates' => $this->getTemplates($paths . '/modules/content/tags/*.blade.php'),
                ],
                [
                    'id' => 'related',
                    'name' => trans('app.Материалы') . ' :: ' . trans('app.Похожие материалы'),
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
        $isRedis = config('cache.default') == 'redis';
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
                    $key = __METHOD__ . '::' . DomainManager::getSiteId() . '::tags';

                    $items = ($isRedis) ? \Cache::tags(['content'])->get($key, null) : \Cache::get($key, null);
                    if (null === $items) {
                        $items = \DB::table(\DB::raw('content_tag ct'))
                            ->select(\DB::raw('ct.text, COUNT(*) as count'))
                            ->groupBy('ct.text')
                            ->orderBy('count', 'desc')
                            ->limit(10)
                            ->get();
                        if ($isRedis) {
                            \Cache::tags(['content'])->put($key, $items, config('cache.ttl_core', 5));
                        } else {
                            \Cache::put($key, $items, config('cache.ttl_core', 5));
                        }
                    }
                    if (isset($data['data']->template->id) && view()->exists($data['data']->template->id)) {
                        return view($data['data']->template->id, [
                            'items' => $items,
                        ])->render();
                    }
                    break;
                case 'content::related':
                    /**
                     * Похожие материалы
                     */
                    $items = [];
                    $key = __METHOD__ . '::' . DomainManager::getSiteId() . '::related';

                    $items = ($isRedis) ? \Cache::tags(['content'])->get($key, null) : \Cache::get($key, null);
                    if (null === $items) {
                        $items = [];
                        $_items = Content::where(function (Builder $query) {
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
                        })->limit($module->getParameterByFilterData(['name' => 'LIMIT'], 5))
                            ->orderBy('published_at', 'desc')->get();

                        /**
                         * @var $contentItem Content
                         */
                        foreach ($_items as $contentItem) {
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
                            array_push($items, $contentItem);
                        }
                    }


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
     * Метод возвращает директорию модуля
     *
     * @return string
     */
    public function getModuleDir()
    {
        return dirname(__FILE__);
    }

    /**
     * Метод удаляет не нужную разметку из текста перед сохранением объектов
     *
     * @param $data
     * @return mixed
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
     * Схема установки модуля
     *
     * @param array $allSteps схема установки системы
     *
     * @return mixed
     */
    public function getInstallStep(&$allSteps)
    {
        $last = array_last(array_keys($allSteps));


        $allSteps[$last]['step'] = 'content_init';
        $allSteps['content_init'] = [
            'title_step' => trans('app.Модуль Материалы: подготовка, создание таблиц'),
            'step' => 'content_install',
            'stop' => false,
            'install' => function ($request) {
                sleep(1);
            },
        ];

        $allSteps['content_install'] = [
            'title_step' => trans('app.Модуль Материалы: таблицы созданы'),
            'step' => '',
            'stop' => false,
            'install' => function ($request) {
                \FastDog\Content\Entity\Content::createDbSchema();
                ContentCanonical::createDbSchema();
                ContentCanonicalCheckResult::createDbSchema();
                ContentCategory::createDbSchema();
                ContentComments::createDbSchema();
                ContentConfig::createDbSchema();
                ContentStatistic::createDbSchema();
                ContentTag::createDbSchema();
                sleep(1);
            },
        ];

        return $allSteps;
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
        $result = [];

        array_push($result, [
            'name' => '<i class="fa fa-table"></i> ' . trans('app.Управление'),
            'route' => '/content/items',
        ]);

        array_push($result, [
            'name' => '<i class="fa fa-table"></i> ' . trans('app.Категории'),
            'route' => '/content/category/items',
        ]);

        array_push($result, [
            'name' => '<i class="fa fa-power-off"></i> ' . trans('app.Диагностика'),
            'route' => '/content/diagnostic',
        ]);

        array_push($result, [
            'name' => '<i class="fa fa-gears"></i> ' . trans('app.Настройки'),
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

    /**
     * Возвращает массив таблиц для резервного копирования
     *
     * @return array
     */
    public function getTables()
    {
        // TODO: Implement getTables() method.
    }
}
