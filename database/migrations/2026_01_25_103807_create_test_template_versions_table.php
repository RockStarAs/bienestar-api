<?php

use App\Models\TestTemplateVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestTemplateVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_template_versions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('template_id');
            $table->string('version', 20); // ej: "1", "v1", "2025-I", "1.0"
            $table->string('status', 20)->default(TestTemplateVersion::STATUS_DRAFT); // draft|published
            $table->unsignedBigInteger('created_by');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('test_templates')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->unique(['template_id', 'version']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_template_versions');
    }
}
