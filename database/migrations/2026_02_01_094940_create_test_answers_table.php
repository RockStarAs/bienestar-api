<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_assignment_id');
            $table->unsignedBigInteger('question_id'); // Referencia a template_questions
            $table->unsignedBigInteger('option_id')->nullable(); // Referencia a template_question_options
            $table->text('text_value')->nullable(); // Para respuestas abiertas
            $table->timestamps();

            $table->foreign('test_assignment_id')->references('id')->on('test_assignments')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('template_questions')->onDelete('cascade');
            $table->foreign('option_id')->references('id')->on('template_question_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_answers');
    }
}
