<?php

namespace Dkvadratu\FilamentSeoExtended\Tests;

use Dkvadratu\FilamentSeoExtended\Models\Seo;
use Dkvadratu\FilamentSeoExtended\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Orchestra\Testbench\TestCase;
use RalphJSmit\Laravel\SEO\LaravelSEOServiceProvider;
use RalphJSmit\Laravel\SEO\SchemaCollection;
use RalphJSmit\Laravel\SEO\Support\SchemaTagCollection;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class SeoExtensionTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelSEOServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_it_accepts_only_nonempty_objects_or_lists_of_objects(): void
    {
        $this->assertSame([['@type' => 'Organization']], Schema::decode('{"@type":"Organization"}'));
        $this->assertCount(2, Schema::decode('[{"@type":"Review"},{"@type":"FAQPage"}]'));
        foreach (['null', '[]', '{}', '"text"', '[1]', '{broken'] as $invalid) {
            $this->assertNull(Schema::decode($invalid));
        }
    }

    public function test_manual_schema_replaces_upstream_dynamic_schema_and_is_safely_encoded(): void
    {
        $seo = new Seo(['schema_json' => '{"@type":"Review","reviewBody":"</script><script>alert(1)</script>"}']);
        $seo->setRelation('model', new SeoTestModel);
        $data = $seo->prepareForUsage();
        $this->assertSame('Review', $data->schema->first()['@type']);
        $html = SchemaTagCollection::initialize($data)->render();
        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringContainsString('\\u003C', $html);
    }

    public function test_empty_or_invalid_manual_data_keeps_dynamic_schema(): void
    {
        foreach ([null, '{broken'] as $value) {
            $seo = new Seo(['schema_json' => $value]);
            $seo->setRelation('model', new SeoTestModel);
            $this->assertSame('FAQPage', $seo->prepareForUsage()->schema->first()['@type']);
        }
    }

    public function test_migration_preserves_existing_rows_and_preexisting_column(): void
    {
        DatabaseSchema::create('seo', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
        });
        DB::table('seo')->insert(['title' => 'Existing title']);
        $migration = require __DIR__.'/../database/migrations/add_schema_json_to_seo_table.php.stub';
        $migration->up();
        $migration->up();
        $this->assertTrue(DatabaseSchema::hasColumn('seo', 'schema_json'));
        $this->assertSame('Existing title', DB::table('seo')->value('title'));
        DB::table('seo')->update(['schema_json' => '{}']);
        $migration->down();
        $this->assertSame('{}', DB::table('seo')->value('schema_json'));
    }
}

class SeoTestModel extends Model
{
    public function getDynamicSEOData(): SEOData
    {
        return new SEOData(schema: new SchemaCollection([['@type' => 'FAQPage']]));
    }
}
