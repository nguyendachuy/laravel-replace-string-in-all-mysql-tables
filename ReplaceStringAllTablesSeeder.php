<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReplaceStringAllTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->handle(env('DB_DATABASE'));
    }
    private function handle($database)
    {
        $data = DB::table(DB::raw('information_schema.columns'))
            ->whereRaw("table_schema = '$database'
            and DATA_TYPE in ('varchar','text','longtext')
            and COLUMN_NAME not in ('id','type','email','password','tags','size','phone_number',
                                    'code','remember_token','slug','key','status','created_at','updated_at','deleted_at')
            and TABLE_NAME not in ('media_files','migrations','system_jobs','roles','role_has_permissions','model_has_permissions','model_has_roles')
            and TABLE_NAME not like 'mail_%'
            and TABLE_NAME not like 'form_%'")
            ->orderBy(DB::raw('table_name,ordinal_position'))
            ->select(DB::raw('TABLE_NAME, COLUMN_NAME'))
            ->get();
        $data = collect($data);
        $results = $data->groupBy([
            function ($item) {
                return $item->TABLE_NAME;
            },
        ], $preserveKeys = true);
        
        $searchTxt = '.png'; 
        $changeToTxt = '.svg'; 
        foreach ($results as $table => $columns) {
            $sets = [];
            foreach ($columns->pluck(['COLUMN_NAME'])->all() as $column) {
                $sets[] = "`$column` = REPLACE(`$column`, $searchTxt , $changeToTxt)";
            }
            $query = "UPDATE $table SET " . implode(' , ', $sets);
            try {
                DB::statement($query);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }
}

//Create seeder file: php artisan make:seeder ReplaceStringAllTablesSeeder
//Run: php artisan db:seed --class=ReplaceStringAllTablesSeeder
