<?php

use App\Models\Test;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('template_version_id');
            $table->string('title', 200);
            $table->string('status', 20)->default(Test::STATUS_ACTIVE); // active|closed
            $table->unsignedBigInteger('created_by');

            $table->timestamps();

            $table->foreign('template_version_id')->references('id')->on('test_template_versions')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->index(['status']);
            $table->index(['template_version_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tests');
    }
}
