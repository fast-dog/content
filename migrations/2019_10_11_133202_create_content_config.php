<?php

use FastDog\Content\Models\ContentConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('content_config')) {
            Schema::create('content_config', function (Blueprint $table) {
                $table->increments('id');
                $table->string(ContentConfig::NAME)->comment('Название');
                $table->string(ContentConfig::ALIAS)->comment('Псевдоним');
                $table->json(ContentConfig::VALUE)->comment('Значение');
                $table->tinyInteger('priority');
                $table->timestamps();
                $table->softDeletes();
                $table->unique(ContentConfig::ALIAS, 'UK_content_config_alias');

            });
            DB::statement("ALTER TABLE `content_config` comment 'Параметры модуля Материалы'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_config');
    }
}
