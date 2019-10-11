<?php

use FastDog\Content\Entity\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('content')) {
            Schema::create('content', function (Blueprint $table) {
                $table->increments('id');
                $table->string(Content::NAME)->comment('Название');
                $table->string(Content::ALIAS)->comment('Псевдоним');
                $table->integer(Content::CATEGORY_ID)->comment('Идентификатор категории');
                $table->tinyInteger(Content::FAVORITES)->default(0)->comment('В избранном');
                $table->integer(Content::VIEW_COUNTER)->default(0)->comment('Кол-во просмотров');
                $table->mediumInteger(Content::SORT)->default(100);
                $table->text('introtext');
                $table->text('fulltext');
                $table->tinyInteger(Content::STATE)->default(Content::STATE_PUBLISHED);
                $table->timestamps();
                $table->softDeletes();
                $table->dateTime('published_at');
                $table->json(Content::DATA)->comment('Дополнительные параметры');
                $table->char(Content::SITE_ID, 3)->default('000');
                $table->integer(Content::COUNT_COMMENT)->default(0)->comment('кол-во комментариев');
                $table->index(Content::ALIAS, 'IDX_content_alias');
                $table->index(Content::CATEGORY_ID, 'IDX_content_category_id');
                $table->index(Content::SITE_ID, 'IDX_content_site_id');
            });
            DB::statement("ALTER TABLE `content` comment 'Материалы'");
            DB::unprepared("DROP TRIGGER IF EXISTS content_before_insert");

            $user = config('database.connections.mysql.username');
            $host = config('database.connections.mysql.host');
            DB::unprepared("
CREATE 
	DEFINER = '{$user}'@'{$host}'
TRIGGER content_before_insert
	BEFORE INSERT
	ON content
	FOR EACH ROW
BEGIN
  IF (NEW.state > 0) THEN
    SET NEW.published_at = NOW();
  END IF;
END
            ");

            DB::unprepared("DROP TRIGGER IF EXISTS content_before_update");
            DB::unprepared("
CREATE 
	DEFINER = '{$user}'@'{$host}'
TRIGGER content_before_update
	BEFORE UPDATE
	ON content
	FOR EACH ROW
BEGIN
  IF (NEW.state > 0 AND OLD.state != 1) THEN
    SET NEW.published_at = NOW();
  END IF;

  IF (NEW.state != 1) THEN
    SET NEW.published_at = NULL;
  END IF;

  IF (NEW.view_counter <> OLD.view_counter) THEN
    SET @statisticId = (SELECT
        ID
      FROM content_statistic_views AS csv
      WHERE DATE_FORMAT(csv.created_at, '%Y-%m-%d') = CURDATE() LIMIT 1);

    IF (@statisticId > 0) THEN
      UPDATE content_statistic_views SET VALUE = VALUE + 1 WHERE ID = @statisticId;
      ELSE  
      INSERT LOW_PRIORITY INTO content_statistic_views(created_at,value) VALUES (NOW(),1);
    END IF; 
  END IF;
END
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content');
    }
}
