<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Evita que @vite() falle en entornos de test donde no existe public/build/manifest.json
        $this->withoutVite();
    }
}
