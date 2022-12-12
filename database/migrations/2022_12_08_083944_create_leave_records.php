<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_records', function (Blueprint $table) {
            $table->increments('lid');
            $table->bigInteger('user_id');
            $table->tinyinteger('type');
            $table->string('comment');
            $table->string('start_date');
            $table->string('end_date');
            $table->tinyinteger('start_hour');
            $table->tinyinteger('end_hour');
            $table->float('period');
            $table->tinyinteger('valid_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_records');
    }
};
