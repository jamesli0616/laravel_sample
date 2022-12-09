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
            $table->bigInteger('user_id');
            $table->string('leave_date');
            $table->tinyinteger('leave_type');
            $table->string('leave_comment');
            $table->tinyinteger('leave_start');
            $table->tinyinteger('leave_period');
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
