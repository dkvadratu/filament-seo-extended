<?php

namespace Dkvadratu\FilamentSeoExtended\Models;

use Dkvadratu\FilamentSeoExtended\Schema;
use RalphJSmit\Laravel\SEO\Models\SEO as BaseSeo;
use RalphJSmit\Laravel\SEO\SchemaCollection;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class Seo extends BaseSeo
{
    public function prepareForUsage(): SEOData
    {
        $seo = parent::prepareForUsage();
        $schemas = Schema::decode($this->getAttributes()['schema_json'] ?? null);

        if ($schemas !== null) {
            $seo->schema = new SchemaCollection($schemas);
        }

        return $seo;
    }
}
