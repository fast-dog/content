<?php

use FastDog\Content\Models\ContentCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('content_category')) {
            Schema::create('content_category', function (Blueprint $table) {
                $table->increments('id');
                $table->string(ContentCategory::NAME)->comment('Название');
                $table->string(ContentCategory::ALIAS)->comment('Псевдоним');
                $table->json(ContentCategory::DATA)->comment('Дополнительные параметры');
                $table->tinyInteger(ContentCategory::STATE)->default(ContentCategory::STATE_PUBLISHED)->comment('Состояние');
                $table->char(ContentCategory::SITE_ID, 3)->default('000')->comment('Код сайта');
                $table->index(ContentCategory::SITE_ID, 'IDX_content_category_site_id');
                $table->unique([ContentCategory::ALIAS, ContentCategory::SITE_ID], 'UK_content_category_alias');
                $table->timestamps();
                $table->softDeletes();

            });
            DB::statement("ALTER TABLE `content_category` comment 'Категории материалов'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_category');
    }
}
