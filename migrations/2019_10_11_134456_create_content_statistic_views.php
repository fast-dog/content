<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentStatisticViews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('content_statistic_views')) {
            Schema::create('content_statistic_views', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('value');
                $table->timestamps();
            });
            DB::statement("ALTER TABLE `content_statistic_views` comment 'Статистика просмотра материалов'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_statistic_views');
    }
}
