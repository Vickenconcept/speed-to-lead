<?php

namespace Tests\Unit;

use App\Services\BusinessImport\GoogleMapsReader;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleMapsReaderTest extends TestCase
{
    public function test_a_maps_link_is_read_from_the_place_preview(): void
    {
        Http::fake([
            'https://maps.app.goo.gl/*' => Http::response('<html><a href="/maps/preview/place?q=sample&amp;hl=en">place</a></html>'),
            'https://www.google.com/maps/preview/place*' => Http::response(<<<'JSON'
            )]}'
            [null,["token",null,null,null,null,null,null,null,null,[null,null,1,2],"0xabc:0xdef","Sample Shop",null,["Bakery","Cafe"],null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,"Africa/Lagos",null,["Sample Shop bakes fresh bread for the neighborhood. Orders are ready the same day."],null,["+234 803 000 0000",["tel:+2348030000000"]],null,[["Tuesday",[["Open 24 hours"]]]]]]
            JSON),
        ]);

        $place = (new GoogleMapsReader)->read('https://maps.app.goo.gl/example');

        $this->assertSame('Sample Shop', $place['name']);
        $this->assertSame('+234 803 000 0000', $place['phone']);
        $this->assertSame('Africa/Lagos', $place['timezone']);
        $this->assertSame('00:00', $place['hours'][0]['opens']);
        $this->assertSame('23:59', $place['hours'][0]['closes']);
        $this->assertCount(7, $place['hours']);
        $this->assertStringContainsString('Categories: Bakery, Cafe', $place['text']);
        $this->assertStringContainsString('fresh bread', $place['text']);
    }
}
