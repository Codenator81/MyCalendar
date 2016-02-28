<?php namespace KurtJensen\MyCalendar\Updates;

use DB;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddEventFields extends Migration {
    
    public function up() {
        Schema::table('kurtjensen_mycal_events', function ($table) {
            $table->text('excerpt')->nullable();
            $table->date('multidate')->nullable();
            $table->date('excluded')->nullable();
            $table->integer('allday')->nullable()->unsigned();
            $table->date('thru')->nullable();
            $table->text('recur')->nullable();
        });
    }

    public function down() {
        Schema::table('kurtjensen_mycal_events', function ($table) {
            $table->dropColumn('excerpt');
            $table->dropColumn('multidate');
            $table->dropColumn('excluded');
            $table->dropColumn('allday');
            $table->dropColumn('thru');
            $table->dropColumn('recur');
        });
    }
}