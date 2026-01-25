<?php

use App\Models\TestTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_templates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('status', 20)->default(TestTemplate::STATUS_DRAFT); // draft|published
            $table->unsignedBigInteger('created_by');

            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
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
        Schema::dropIfExists('test_templates');
    }
}
