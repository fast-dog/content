<?php

use FastDog\Content\Models\ContentCanonicalCheckResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentCanonicalCheckResult extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('content_canonical_check_result')) {
            Schema::create('content_canonical_check_result', function (Blueprint $table) {
                $table->increments('id');
                $table->integer(ContentCanonicalCheckResult::ITEM_ID);
                $table->string(ContentCanonicalCheckResult::CODE);
                $table->char(ContentCanonicalCheckResult::SITE_ID, 3)->default('000');
                $table->timestamps();
                $table->index(ContentCanonicalCheckResult::SITE_ID, 'IDX_content_canonical_check_site_id');
                $table->index([ContentCanonicalCheckResult::ITEM_ID, ContentCanonicalCheckResult::SITE_ID], 'IDX_content_canonical_check_re');
            });
            DB::statement("ALTER TABLE `content_canonical_check_result` comment 'Проверка доступности канонических ссылок'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_canonical_check_result');
    }
}
