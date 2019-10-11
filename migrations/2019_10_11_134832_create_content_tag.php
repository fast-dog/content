<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentTag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('content_tag')) {
            Schema::create('content_tag', function (Blueprint $table) {
                $table->increments('id');
                $table->string('text');
                $table->integer('item_id');

            });
            DB::statement("ALTER TABLE `content_tag` comment 'Теги материалов'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_tag');
    }
}
