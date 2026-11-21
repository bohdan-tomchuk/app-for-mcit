<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_tests_run_on_the_postgres_testing_database()
    {
        $this->assertSame('pgsql', DB::getDriverName());
        $this->assertSame('testing', DB::getDatabaseName());
    }

    public function test_nested_transactions_use_savepoints()
    {
        $outer = DB::transactionLevel();

        DB::beginTransaction();
        $this->assertSame($outer + 1, DB::transactionLevel());
        DB::insert("insert into cache (key, value, expiration) values ('k', 'v', 1)");
        DB::rollBack();

        $this->assertSame($outer, DB::transactionLevel());
        $this->assertSame(0, DB::table('cache')->where('key', 'k')->count());
    }
}
