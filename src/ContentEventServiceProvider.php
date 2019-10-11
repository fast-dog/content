<?php

namespace FastDog\Menu;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ContentEventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
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


    /**
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

}
