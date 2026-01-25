<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateQuestionOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_question_options', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('question_id');
            $table->string('label', 200);
            $table->string('value', 50)->nullable(); // "1".."5" o código
            $table->unsignedInteger('order')->default(1);

            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('template_questions')->onDelete('cascade');

            $table->unique(['question_id', 'order']);
            $table->index(['question_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_question_options');
    }
}
