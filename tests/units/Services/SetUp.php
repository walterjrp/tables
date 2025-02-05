<?php

namespace LaravelEnso\Tables\Tests\units\Services;

use Faker\Factory;
use Illuminate\Support\Facades\Route;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Tables\app\Services\Template;
use LaravelEnso\Tables\app\Services\Data\Config;
use LaravelEnso\Tables\app\Services\Data\Request;
use LaravelEnso\Tables\Tests\units\Services\TestModel;
use LaravelEnso\Tables\Tests\units\Services\TestTable;
use LaravelEnso\Tables\Tests\units\Services\BuilderTestEnum;

trait SetUp
{
    private $faker;
    private $testModel;
    private $table;
    private $config;
    private $query;

    protected function setUp(): void
    {
        parent::setUp();

        // $this->withoutExceptionHandling();

        $this->faker = Factory::create();

        Route::any('route')->name('testTables.tableData');
        Route::getRoutes()->refreshNameLookups();

        TestModel::createTable();

        $this->testModel = $this->createTestModel();

        $columns = $filters = $intervals = $params = [];

        $meta = ['length' => 10, 'search' => '', 'searchMode' => 'full'];

        $request = new Request(
            $columns, $meta, $filters, $intervals, $params
        );

        $request->columns()->push(new Obj([
            'name' => 'name',
            'data' => 'name',
            'meta' => ['searchable' => true],
        ]));

        $this->table = new TestTable();

        $template = (new Template())->build($this->table);

        $this->config = new Config($request, $template);

        $this->query = $this->table->query();
    }

    protected function createTestModel()
    {
        return TestModel::create([
            'name' => $this->faker->name,
            'is_active' => $this->faker->boolean,
            'price' => $this->faker->numberBetween(1000, 10000),
            'color' => BuilderTestEnum::Red,
        ]);
    }
}
