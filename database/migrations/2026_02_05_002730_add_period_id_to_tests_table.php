<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPeriodIdToTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tests', function (Blueprint $table) {
            // Add period_id column as nullable foreign key
            $table->unsignedBigInteger('period_id')->nullable()->after('title');
            
            // Create foreign key constraint with ON DELETE SET NULL
            $table->foreign('period_id')->references('id')->on('periods')->onDelete('set null');
            
            // Add index on period_id for performance
            $table->index('period_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tests', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['period_id']);
            
            // Drop index
            $table->dropIndex(['period_id']);
            
            // Drop the column
            $table->dropColumn('period_id');
        });
    }
}
