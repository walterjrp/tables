<?php

namespace LaravelEnso\Tables\Tests\units\Services\Table\Builders;

use Faker\Factory;
use Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use LaravelEnso\Helpers\app\Classes\Obj;
use Illuminate\Database\Eloquent\Builder;
use LaravelEnso\Tables\app\Contracts\Table;
use LaravelEnso\Tables\app\Services\Template;
use LaravelEnso\Tables\app\Services\Table\Request;
use LaravelEnso\Tables\app\Services\Table\Builders\Meta;

class MetaTest extends TestCase
{
    private $faker;
    private $testModel;
    private $table;
    private $request;
    private $template;

    public function setUp(): void
    {
        parent::setUp();

        // $this->withoutExceptionHandling();

        $this->faker = Factory::create();

        Route::any('route')->name('testTables.tableData');
        Route::getRoutes()->refreshNameLookups();

        $this->request = new Request(['columns' => [], 'meta' => ['length' => 10]]);

        $this->table = (new TestTable())->select(
            'id, name, is_active, created_at, price, color'
        );

        $this->template = (new Template($this->table))->load([
            'template' => new Obj([
                'routePrefix' => 'testTables',
                'buttons' => [],
                'columns' => []
            ]),
            'meta' => new Obj(),
        ]);

        TestModel::createTable();

        $this->testModel = $this->createTestModel();
    }

    /** @test */
    public function can_get_data()
    {
        $response = $this->requestResponse();

        $this->assertEquals(TestModel::count(), $response->get('count'));
    }

    /** @test */
    public function cannot_get_data_cache_count()
    {
        $this->template->put('countCache', false);

        $this->requestResponse();

        $this->assertFalse(Cache::has('enso:tables:testModels'));
    }

    /** @test */
    public function can_get_data_with_cache_when_table_cache_trait_used()
    {
        Config::set('enso.tables.cache.count', true);

        $this->requestResponse();

        $this->assertEquals(1, Cache::get('enso:tables:test_models'));
    }

    /** @test */
    public function can_get_data_with_limit()
    {
        $this->request->meta()->put('length', 0);

        $response = $this->requestResponse();

        $this->assertEquals(1, $response->get('filtered'));
        $this->assertEquals(1, $response->get('count'));
    }

    /** @test */
    public function can_get_data_with_total()
    {
        $this->createTestModel();

        $this->template->columns()->push(new Obj([
            'name' => 'price',
            'data' => 'price',
            'meta' => ['total'],
        ]));

        $this->template->meta()->put('total', true);

        $response = $this->requestResponse();

        $this->assertEquals(
            TestModel::sum('price'),
            $response->get('total')->get('price')
        );
    }

    /** @test */
    public function can_use_full_info_record_limit()
    {
        $limit = 1;

        $this->createTestModel();

        $this->testModel->update(['name' => 'User']);

        $this->template->columns()->push(new Obj([
            'name' => 'name',
            'data' => 'name',
            'meta' => ['searchable' => true]
        ]));

        $this->template->set('comparisonOperator', 'LIKE');

        $this->request->meta()->set('search', $this->testModel->name)
            ->set('fullInfoRecordLimit', $limit)
            ->set('length', $limit)
            ->set('searchMode', 'full');

        $response = $this->requestResponse();

        $this->assertFalse($response->get('fullRecordInfo'));
        $this->assertEquals(2, $response->get('count'));
        $this->assertEquals(2, $response->get('filtered'));
    }

    private function requestResponse()
    {
        $builder = new Meta(
            $this->table, $this->request, $this->template
        );

        return new Obj($builder->data());
    }

    private function createTestModel()
    {
        return TestModel::create([
            'name' => $this->faker->name,
            'is_active' => $this->faker->boolean,
            'price' => $this->faker->numberBetween(1000, 10000),
        ]);
    }
}

