<?php

namespace FastDog\Content\Listeners;

use App\Core\FormFieldTypes;
use App\Modules\Catalog\Entity\CatalogItems;
use App\Modules\Config\Entity\DomainManager;
use FastDog\Content\Entity\Content;
use FastDog\Content\Entity\ContentCategory;
use FastDog\Content\Events\ContentAdminPrepare as EventContentAdminPrepare;
use Illuminate\Http\Request;

/**
 * При редактирование
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentItemAdminSetEditorForm
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * ContentAdminPrepare constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param EventContentAdminPrepare $event
     * @return void
     */
    public function handle(EventContentAdminPrepare $event)
    {
        $data = $event->getData();
        $item = $event->getItem();

        $result = $event->getResult();

        $result['form'] = [
            'create_url' => '',
            'update_url' => '',
            'help' => 'content_item',
            'tabs' => (array)[
                (object)[
                    'id' => 'catalog-item-general-tab',
                    'name' => trans('app.Основная информация'),
                    'active' => true,
                    'fields' => (array)[
                        [
                            'id' => CatalogItems::NAME,
                            'type' => FormFieldTypes::TYPE_TEXT,
                            'name' => CatalogItems::NAME,
                            'label' => trans('app.Название'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'required' => true,
                            'validate' => 'required|min:5',
                        ],
                        [
                            'id' => CatalogItems::ALIAS,
                            'type' => FormFieldTypes::TYPE_TEXT_ALIAS,
                            'name' => CatalogItems::ALIAS,
                            'label' => trans('app.Псевдоним'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                        ],
                        [
                            'id' => 'canonical',
                            'type' => FormFieldTypes::TYPE_SEARCH,
                            'name' => 'canonical',
                            'label' => trans('app.Каноническая ссылка'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                            'data_url' => 'public/content/canonicals',
                            'title' => trans('app.Существующие канонические ссылки'),
                            'filter' => [
                                'id' => $item->id,
                            ],
                        ],
                        [
                            'type' => FormFieldTypes::TYPE_TABS,
                            'tabs' => [
                                [
                                    'id' => 'catalog-item-introtext-tab',
                                    'name' => trans('app.Вступительный текст'),
                                    'active' => true,
                                    'fields' => [
                                        [
                                            'id' => CatalogItems::INTROTEXT,
                                            'type' => FormFieldTypes::TYPE_HTML_EDITOR,
                                            'name' => CatalogItems::INTROTEXT,
                                            'label' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'catalog-item-fulltext-tab',
                                    'name' => trans('app.Полный текст'),
                                    'active' => false,
                                    'fields' => [
                                        [
                                            'id' => CatalogItems::FULLTEXT,
                                            'type' => FormFieldTypes::TYPE_HTML_EDITOR,
                                            'name' => CatalogItems::FULLTEXT,
                                            'label' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'catalog-item-tabs-tab',
                                    'name' => trans('app.Редактор табов'),
                                    'active' => false,
                                    'fields' => [
                                        [
                                            'id' => 'tabs',
                                            'type' => FormFieldTypes::TYPE_TABS_EDITOR,
                                            'name' => 'tabs',
                                            'label' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'side' => [
                        [
                            'id' => CatalogItems::SITE_ID,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => CatalogItems::SITE_ID,
                            'label' => trans('app.Доступ'),
                            'items' => DomainManager::getAccessDomainList(),
                            'css_class' => 'col-sm-12',
                            'active' => DomainManager::checkIsDefault(),
                        ],
                        [
                            'id' => CatalogItems::STATE,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => CatalogItems::STATE,
                            'label' => trans('app.Состояние'),
                            'css_class' => 'col-sm-12',
                            'items' => Content::getStatusList(),
                        ],
                        [
                            'id' => CatalogItems::CATEGORY_ID,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => CatalogItems::CATEGORY_ID,
                            'label' => trans('app.Категория'),
                            'css_class' => 'col-sm-12',
                            'items' => ContentCategory::getCategoryList(true),
                        ],
                        [
                            'id' => CatalogItems::CREATED_AT,
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => CatalogItems::CREATED_AT,
                            'label' => trans('app.Дата создания'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                        ],
                        [
                            'id' => CatalogItems::UPDATED_AT,
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => CatalogItems::UPDATED_AT,
                            'label' => trans('app.Дата обновления'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                        ],
                        [
                            'id' => 'published_at',
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => 'published_at',
                            'label' => trans('app.Дата публикации'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-media-tab',
                    'name' => trans('app.Медиа материалы'),
                    'active' => false,
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_MEDIA,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-seo-tab',
                    'name' => trans('app.Поисковая оптимизация'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_SEO,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-extend-tab',
                    'name' => trans('app.Дополнительно'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_SAMPLE_PROPERTIES,
                            'model_id' => $item->getModelId(),
                            'model' => Content::class,
                        ],
                    ],
                ],
            ],
        ];


        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
        $event->setResult($result);
    }
}