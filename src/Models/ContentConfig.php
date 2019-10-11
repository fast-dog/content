<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 15.01.2017
 * Time: 19:02
 */

namespace FastDog\Content\Entity;


use App\Core\BaseModel;
use App\Core\Desktop;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Параметры модуля
 *
 * @package FastDog\Content\Entity
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentConfig extends BaseModel
{
    /**
     * Тип: визуальный редактор
     * @const string
     */
    const CONFIG_CKEDITOR = 'ckeditor';

    /**
     * Тип: визуальный редактор - шаблоны HTML
     * @const string
     */
    const CONFIG_CKEDITOR_TEMPLATES = 'ckeditor_templates';

    /**
     * Тип: рабочий стол
     * @const string
     */
    const CONFIG_DESKTOP = 'desktop';
    /**
     * Тип: публичный раздел
     * @const string
     */
    const CONFIG_PUBLIC = 'public';
    /**
     * Значение
     * @const string
     */
    const VALUE = 'value';
    /**
     * Название таблицы
     *
     * @var string $table
     */
    public $table = 'content_config';


    /**
     * Все параметры
     *
     * @return array
     */
    public static function getAllConfig()
    {
        $result = [];
        $items = self::where(self::STATE, self::STATE_PUBLISHED)->orderBy('priority')->get();
        foreach ($items as $item) {
            $data = json_decode($item->{'value'});
            /**
             * Проверка состояния блоков на главной странице
             */
            if ($item->alias == self::CONFIG_DESKTOP) {
                foreach ($data as $key => &$value) {
                    $_item = Desktop::where(Desktop::NAME, $value->name)->withTrashed()->first();
                    if ($_item) {
                        $value->value = ($_item->deleted_at === null) ? 'Y' : 'N';
                    }
                }
            }

            $result[$item->alias] = [
                'open' => ($item->alias == \Request::input('open_section', self::CONFIG_DESKTOP)),
                'name' => $item->{self::NAME},
                'config' => $data,
            ];
        }

        return $result;
    }

    /**
     * Детальная информация о объекте
     *
     * @return array
     */
    public function getData(): array
    {
        if (is_string($this->{self::VALUE})) {
            $this->{self::VALUE} = json_decode($this->{self::VALUE});
        }
        $result = [
            'id' => $this->id,
            self::NAME => $this->{self::NAME},
            self::ALIAS => $this->{self::ALIAS},
            self::VALUE => $this->{self::VALUE},
        ];

        return $result;
    }

    /**
     * Проверка доступа по ключу
     *
     * @param $access_name
     * @return bool
     */
    public function can($access_name)
    {
        $data = $this->getData();

        foreach ($data[self::VALUE] as $item) {
            if ($item->{'alias'} === $access_name) {
                switch ($item->{'type'}) {
                    case 'select':
                        return ($item->{'value'} === 'Y');
                }
            }
        }

        return false;
    }

    /**
     * Создание таблицы базы данных
     *
     * Будут созданы таблицы и триггеры:
     * <pre>
     * CREATE TABLE content_config (
     *          id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     *          name varchar(255) NOT NULL COMMENT 'Название',
     *          alias varchar(255) NOT NULL COMMENT 'Псевдоним',
     *          value json NOT NULL COMMENT 'Значение',
     *          priority tinyint(4) NOT NULL,
     *          created_at timestamp NULL DEFAULT NULL,
     *          updated_at timestamp NULL DEFAULT NULL,
     *          deleted_at timestamp NULL DEFAULT NULL,
     *          PRIMARY KEY (id),
     *          UNIQUE INDEX UK_content_config_alias (alias)
     * )
     * COMMENT = 'Параметры модуля Материалы';
     * </pre>
     *
     * @return void
     */
    public static function createDbSchema()
    {
        if (!Schema::hasTable('content_config')) {
            Schema::create('content_config', function (Blueprint $table) {
                $table->increments('id');
                $table->string(self::NAME)->comment('Название');
                $table->string(self::ALIAS)->comment('Псевдоним');
                $table->json(self::VALUE)->comment('Значение');
                $table->tinyInteger('priority');
                $table->timestamps();
                $table->softDeletes();
                $table->unique(self::ALIAS, 'UK_content_config_alias');

            });
            DB::statement("ALTER TABLE `content_config` comment 'Параметры модуля Материалы'");
        }
    }
}