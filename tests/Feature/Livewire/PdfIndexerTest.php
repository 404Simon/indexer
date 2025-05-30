<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PdfIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class PdfIndexerTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(PdfIndexer::class)
            ->assertStatus(200);
    }
}
