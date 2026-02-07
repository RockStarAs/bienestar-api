<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_questions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('template_version_id');
            $table->string('section', 150)->nullable(); // DATOS PERSONALES, etc.
            $table->text('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->text('text'); //representa la pregunta en sí
            $table->string('type', 30); // text|date|single_choice|multiple_choice|likert|grouped|grouped_child
            //este campo nos ayudara a autoreferenciar, en caso necesitemos tener preguntas agrupadas
            //se supone que las preguntas agrupadas tiene las mismas opciones
            $table->unsignedBigInteger('parent_question_id')->nullable();
            $table->boolean('required')->default(true);
            $table->unsignedInteger('order')->default(1);

            $table->timestamps();

            $table->foreign('template_version_id')->references('id')->on('test_template_versions')->onDelete('cascade');
            $table->foreign('parent_question_id')->references('id')->on('template_questions')->onDelete('cascade');

            $table->unique(['template_version_id', 'order']);
            $table->index(['template_version_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_questions');
    }
}
