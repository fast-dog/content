<?php

use FastDog\Content\Models\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use \FastDog\Content\Models\ContentCanonical;

class CreateContentCanonical extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('content_canonical')) {
            Schema::create('content_canonical', function (Blueprint $table) {
                $table->increments('id');
                $table->tinyInteger(ContentCanonical::IS_INDEX);
                $table->integer(ContentCanonical::ITEM_ID);
                $table->tinyInteger(ContentCanonical::TYPE);
                $table->string(ContentCanonical::LINK);
                $table->char(Content::SITE_ID, 3)->default('000');
                $table->timestamps();
                $table->softDeletes();
                $table->unique([ContentCanonical::ITEM_ID, ContentCanonical::TYPE, ContentCanonical::LINK], 'UK_content_canonical');
            });
            DB::statement("ALTER TABLE `content_canonical` comment 'Канонические ссылки'");

            DB::unprepared("DROP FUNCTION IF EXISTS selectCountContentCanonical");
            DB::unprepared("
CREATE FUNCTION selectCountContentCanonical(item_id INT)
  RETURNS int(11)
  DETERMINISTIC
  SQL SECURITY INVOKER
BEGIN
RETURN (SELECT COUNT(*) FROM content_canonical cc WHERE cc.item_id = item_id);
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
        Schema::dropIfExists('content_canonical');
        DB::unprepared("DROP FUNCTION IF EXISTS selectCountContentCanonical");
    }
}
