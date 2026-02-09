<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyUniqueIndexOnTemplateQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('template_questions', function (Blueprint $table) {
            // Eliminar el índice único anterior que causaba conflicto entre padres e hijos
            $table->dropUnique(['template_version_id', 'order']);
            
            // Nuevo índice que incluye al padre. 
            // Esto permite que el orden '1' exista para una pregunta raíz y para una hija de 'X'.
            // Nota: En MySQL, múltiples (ver_id, NULL, order) son permitidos, lo que relaja la restricción para raíces,
            // pero soluciona el bloqueo reportado.
            $table->unique(['template_version_id', 'parent_question_id', 'order'], 'temp_q_ver_parent_ord_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->dropUnique('temp_q_ver_parent_ord_unique');
            $table->unique(['template_version_id', 'order']);
        });
    }
}
