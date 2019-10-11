<?php

use FastDog\Content\Models\ContentComments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('content_comments')) {
            Schema::create('content_comments', function (Blueprint $table) {
                $table->increments('id');
                $table->integer(ContentComments::ITEM_ID)->comment('Идентификатор материала');
                $table->integer('lft');
                $table->integer('rgt');
                $table->integer('depth');
                $table->integer('parent_id');
                $table->char(ContentComments::SITE_ID, 3)->default('000');
                $table->tinyInteger(ContentComments::STATE)->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->text('text');
                $table->json(ContentComments::DATA);
                $table->index(ContentComments::SITE_ID, 'IDX_content_site_id');
            });
            DB::statement("ALTER TABLE `content_comments` comment 'Комментарии материалов'");

            DB::unprepared("DROP TRIGGER IF EXISTS content_comment_after_update");

            $user = config('database.connections.mysql.username');
            $host = config('database.connections.mysql.host');

            DB::unprepared("
CREATE 
	DEFINER = '{$user}'@'{$host}'
TRIGGER content_comment_after_update
	AFTER UPDATE
	ON content_comments
	FOR EACH ROW
BEGIN

IF(NEW.lft = 1) THEN
set @count = (NEW.rgt - NEW.lft - 1) /2;
UPDATE content set count_comment=@count WHERE id = NEW.item_id LIMIT 1;
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
        Schema::dropIfExists('content_comments');
    }
}
