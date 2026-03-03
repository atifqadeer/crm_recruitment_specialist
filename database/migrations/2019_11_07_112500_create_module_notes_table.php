<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModuleNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('module_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module_note_uid')->nullable();
            $table->bigInteger('user_id');
            $table->string('module_noteable_id')->nullable();
            $table->string('module_noteable_type')->nullable();
            $table->longText('details')->unique();
            $table->string('module_note_added_date');
            $table->string('module_note_added_time');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('module_notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('module_notes');
    }
}
