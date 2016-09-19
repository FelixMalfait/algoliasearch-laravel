<?php

namespace AlgoliaSearch\Tests;

use AlgoliaSearch\Tests\Models\Model10;
use AlgoliaSearch\Tests\Models\Model11;
use AlgoliaSearch\Tests\Models\Model2;
use AlgoliaSearch\Tests\Models\Model4;
use AlgoliaSearch\Tests\Models\Model6;
use Illuminate\Support\Facades\App;
use Mockery;
use Orchestra\Testbench\TestCase;

class AlgoliaEloquentTraitTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app->config->set('algolia', ['default' => 'main', 'connections' => ['main' => ['id' => 'your-application-id', 'key' => 'your-api-key'], 'alternative' => ['id' => 'your-application-id', 'key' => 'your-api-key']]]);
    }

    public function testGetAlgoliaRecordDefault()
    {
        $this->assertEquals(['id2' => 1, 'objectID' => 1], (new Model2())->getAlgoliaRecordDefault('test'));
        $this->assertEquals(['id2' => 1, 'objectID' => 1, 'id3' => 1, 'name' => 'test'], (new Model4())->getAlgoliaRecordDefault('test'));
    }

    public function testPushToindex()
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $realModelHelper */
        $realModelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $modelHelper = Mockery::mock('\AlgoliaSearch\Laravel\ModelHelper');

        $index = Mockery::mock('\AlgoliaSearch\Index');

        $modelHelper->shouldReceive('getIndices')->andReturn([$index, $index]);
        $modelHelper->shouldReceive('getObjectId')->andReturn($realModelHelper->getObjectId(new Model4()));
        $modelHelper->shouldReceive('indexOnly')->andReturn(true);

        App::instance('\AlgoliaSearch\Laravel\ModelHelper', $modelHelper);

        $index->shouldReceive('addObject')->times(2)->with((new Model4())->getAlgoliaRecordDefault('test'));

        $this->assertEquals(null, (new Model4())->pushToIndex());
    }

    public function testRemoveFromIndex()
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $realModelHelper */
        $realModelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $modelHelper = Mockery::mock('\AlgoliaSearch\Laravel\ModelHelper');

        $index = Mockery::mock('\AlgoliaSearch\Index');

        $modelHelper->shouldReceive('getIndices')->andReturn([$index, $index]);
        $modelHelper->shouldReceive('getObjectId')->andReturn($realModelHelper->getObjectId(new Model4()));

        App::instance('\AlgoliaSearch\Laravel\ModelHelper', $modelHelper);

        $index->shouldReceive('deleteObject')->times(2)->with(1);

        $this->assertEquals(null, (new Model4())->removeFromIndex());
    }

    public function testSetSettings()
    {
        $index = Mockery::mock('\AlgoliaSearch\Index');
        $index->shouldReceive('setSettings')->with(['slaves' => ['model_6_desc_testing']]);
        $index->shouldReceive('setSettings')->with(['ranking' => ['desc(name)']]);

        /** @var \AlgoliaSearch\Laravel\ModelHelper $realModelHelper */
        $realModelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');
        $modelHelper = Mockery::mock('\AlgoliaSearch\Laravel\ModelHelper');

        App::instance('\AlgoliaSearch\Laravel\ModelHelper', $modelHelper);

        $model6 = new Model6();
        $modelHelper->shouldReceive('getSettings')->andReturn($realModelHelper->getSettings($model6));
        $modelHelper->shouldReceive('getIndices')->andReturn([$index]);
        $modelHelper->shouldReceive('getFinalIndexName')->andReturn($realModelHelper->getFinalIndexName($model6, 'model_6_desc'));
        $modelHelper->shouldReceive('getSlavesSettings')->andReturn($realModelHelper->getSlavesSettings($model6));

        $this->assertEquals($modelHelper->getFinalIndexName($model6, $realModelHelper->getSettings($model6)['slaves'][0]), 'model_6_desc_testing');

        $model6->setSettings();
    }

    public function testSetSynonyms()
    {
        $index = Mockery::mock('\AlgoliaSearch\Index');
        $index->shouldReceive('batchSynonyms')->with(
            [
                [
                    'objectID' => 'red-color',
                    'type'     => 'synonym',
                    'synonyms' => [
                        'red',
                        'really red',
                        'much red',
                    ],
                ],
            ],
            true,
            true
        );
        $index->shouldReceive('setSettings');

        /** @var \AlgoliaSearch\Laravel\ModelHelper $realModelHelper */
        $realModelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');
        $modelHelper = Mockery::mock('\AlgoliaSearch\Laravel\ModelHelper');
        App::instance('\AlgoliaSearch\Laravel\ModelHelper', $modelHelper);
        $model10 = new Model10();
        $modelHelper->shouldReceive('getSettings')->andReturn($realModelHelper->getSettings($model10));
        $modelHelper->shouldReceive('getIndices')->andReturn([$index]);
        $modelHelper->shouldReceive('getSlavesSettings')->andReturn($realModelHelper->getSlavesSettings($model10));

        $this->assertEquals(null, $model10->setSettings());
    }

    public function testPustToIndexWithgetAlgoliaRecordAndIndexName()
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $realModelHelper */
        $realModelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $modelHelper = Mockery::mock('\AlgoliaSearch\Laravel\ModelHelper');

        $realindices = $realModelHelper->getIndices(new Model11());
        $realindex = $realindices[0];
        $index = Mockery::mock('\AlgoliaSearch\Index');
        $index->indexName = $realindex->indexName;

        $modelHelper->shouldReceive('getIndices')->andReturn([$index]);
        $modelHelper->shouldReceive('getObjectId')->andReturn($realModelHelper->getObjectId(new Model11()));
        $modelHelper->shouldReceive('indexOnly')->andReturn(true);

        App::instance('\AlgoliaSearch\Laravel\ModelHelper', $modelHelper);


        $index->shouldReceive('addObject')->times(1)->with(['is' => 'working', 'objectID' => null]);

        $this->assertEquals(null, (new Model11())->pushToIndex());
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
