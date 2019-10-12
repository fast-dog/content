<?php

use FastDog\Content\Content;
use FastDog\Core\Models\DomainManager;
use FastDog\Menu\Models\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

Route::group([
    'prefix' => config('core.admin_path', 'admin'),
    'middleware' => ['web', FastDog\Admin\Http\Middleware\Admin::class],
],
    function () {

        $baseParameters = ['middleware' => ['acl'], 'is' => DomainManager::getSiteId() . '::admin',];

        /**
         * Таблица
         */
        \Route::post('/public/content/list', array_replace_recursive($baseParameters, [
            'uses' => '\FastDog\Content\Controllers\Admin\ContentTableController@list',
        ]));

        /**
         * Форма
         */
        $ctrl = '\FastDog\Content\Controllers\Admin\ContentFormController';

        // Отдельный объект
        \Route::get('/public/content/{id}', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getEditItem',
        ]))->where('id', '[1-90]+');

        // Обновление объекта из списка (публикаций, перемещение в корзинку)
        \Route::post('/public/content/update', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postContentUpdate',
        ]));

        // Обновление объекта из списка (публикаций, перемещение в корзинку)
        \Route::post('/public/content/list/self-update', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postContentUpdate',
        ]));

        // Обновлени объекта
        \Route::post('/public/content', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postContent',
        ]));

        // Добавление материала
        \Route::post('/public/add-content', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postContent',
        ]));

        // Копирование объекта
        \Route::post('/public/content/replicate', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postReplicate',
        ]));

        // Удаление значения дополнительного параметра
        \Route::post('/content/delete-select-value', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postDeleteSelectValue',
        ]));

        // Добавление значения дополнительного параметра
        \Route::post('/content/add-select-value', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postAddSelectValue',
        ]));



        /**
         * API
         */
        $ctrl = '\FastDog\Content\Controllers\Admin\ApiController';

        \Route::get('/public/content/admin-info', $ctrl . '@getAdminInfo');

        // Очистка кэша
        \Route::post('/public/content/clear-cache', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postClearCache',
        ]));
        // Изменение разрешений для модуля
        \Route::post('/public/content/access', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postAccess',
        ]));
        // Сохранение параметров модуля
        \Route::post('/public/content/save-module-configurations', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postSaveModuleConfigurations',
        ]));

        // Поиск канонических ссылок на материалы
        \Route::post('/public/content/canonicals', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postCanonicals',
        ]));

        // Страница диагностики
        \Route::get('/public/content/diagnostic', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getDiagnostic',
        ]));

        // Таблица проверки канонических ссылок на материалы
        \Route::post('/public/content/check-canonical', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postCheckCanonical',
        ]));

        // Проверка канонических ссылок на материалы
        \Route::get('/public/content/check-canonical', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getCheckCanonical',
        ]));

        //шаблоны редактора
        \Route::get('/public/content/ck-templates.js', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getCkTemplates',
        ]));

        //поиск материалов
        \Route::post('/public/content/search-list', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getPageSearch',
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
        ]));
        /**
         * Форма
         */
        $ctrl = '\FastDog\Content\Controllers\Admin\Category\FormController';

        // Форма редактирования категории
        \Route::get('/public/content/category/{id}', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getEditItem',
        ]));

        // Сохранение категории
        \Route::post('/public/content/category', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postCategory',
        ]));

//        //обновление объекта
        \Route::post('/public/content/category/list/self-update', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postUpdate',
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
);

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

    if (env('CACHE_DRIVER') == 'redis') {
        \Cache::tags(['core'])->put($key, $data, config('cache.content.route', 5));
    } else {
        \Cache::put($key, $data, config('cache.content.route', 5));
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
            $contentItem = Content::where([
                Content::ALIAS => $alias,
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