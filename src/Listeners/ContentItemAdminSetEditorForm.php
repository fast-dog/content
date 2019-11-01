<?php

namespace FastDog\Content\Listeners;


use FastDog\Content\Content;
use FastDog\Content\Events\ContentAdminPrepare as EventContentAdminPrepare;
use FastDog\Content\Models\ContentCategory;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\FormFieldTypes;
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
                    'name' => trans('content::forms.general.title'),
                    'active' => true,
                    'fields' => (array)[
                        [
                            'id' => Content::NAME,
                            'type' => FormFieldTypes::TYPE_TEXT,
                            'name' => Content::NAME,
                            'label' => trans('content::forms.fields.name'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'required' => true,
                            'validate' => 'required|min:5',
                        ],
                        [
                            'id' => Content::ALIAS,
                            'type' => FormFieldTypes::TYPE_TEXT_ALIAS,
                            'name' => Content::ALIAS,
                            'label' => trans('content::forms.fields.alias'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                        ],
                        [
                            'id' => 'canonical',
                            'type' => FormFieldTypes::TYPE_SEARCH,
                            'name' => 'canonical',
                            'label' => trans('content::forms.fields.canonical_link'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                            'data_url' => 'public/content/canonicals',
                            'title' => trans('content::forms.fields.canonical_link_exists'),
                            'filter' => [
                                'id' => $item->id,
                            ],
                        ],
                        [
                            'type' => FormFieldTypes::TYPE_TABS,
                            'tabs' => [
                                [
                                    'id' => 'catalog-item-introtext-tab',
                                    'name' => trans('content::forms.fields.introtext'),
                                    'active' => true,
                                    'fields' => [
                                        [
                                            'id' => Content::INTROTEXT,
                                            'type' => FormFieldTypes::TYPE_HTML_EDITOR,
                                            'name' => Content::INTROTEXT,
                                            'label' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'catalog-item-fulltext-tab',
                                    'name' => trans('content::forms.fields.fulltext'),
                                    'active' => false,
                                    'fields' => [
                                        [
                                            'id' => Content::FULLTEXT,
                                            'type' => FormFieldTypes::TYPE_HTML_EDITOR,
                                            'name' => Content::FULLTEXT,
                                            'label' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'catalog-item-tabs-tab',
                                    'name' => trans('content::forms.fields.tabs'),
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
                            'id' => Content::SITE_ID,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => Content::SITE_ID,
                            'label' => trans('content::forms.fields.access'),
                            'items' => DomainManager::getAccessDomainList(),
                            'css_class' => 'col-sm-12',
                            'active' => DomainManager::checkIsDefault(),
                        ],
                        [
                            'id' => Content::STATE,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => Content::STATE,
                            'label' => trans('content::forms.fields.state'),
                            'css_class' => 'col-sm-12',
                            'items' => Content::getStatusList(),
                        ],
                        [
                            'id' => Content::CATEGORY_ID,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => Content::CATEGORY_ID,
                            'label' => trans('content::forms.fields.category'),
                            'css_class' => 'col-sm-12',
                            'items' => ContentCategory::getCategoryList(true),
                        ],
                        [
                            'id' => Content::CREATED_AT,
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => Content::CREATED_AT,
                            'label' => trans('content::forms.fields.created_at'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                        ],
                        [
                            'id' => Content::UPDATED_AT,
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => Content::UPDATED_AT,
                            'label' => trans('content::forms.fields.updated_at'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                        ],
                        [
                            'id' => 'published_at',
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => 'published_at',
                            'label' => trans('content::forms.fields.published_at'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-media-tab',
                    'name' => trans('content::forms.media.title'),
                    'active' => false,
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_MEDIA,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-seo-tab',
                    'name' => trans('content::forms.seo.title'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_SEO,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-extend-tab',
                    'name' => trans('content::forms.extend.title'),
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