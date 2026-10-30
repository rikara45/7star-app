<?php

namespace Tests\Unit;

use App\Support\PortfolioCategories;
use PHPUnit\Framework\TestCase;

class PortfolioCategoriesTest extends TestCase
{
    public function test_daftar_kategori_tetap_sepuluh_dan_berpetunjuk(): void
    {
        $categories = PortfolioCategories::all();

        $this->assertCount(10, $categories);

        foreach ($categories as $name => $hint) {
            $this->assertNotSame('', trim($name));
            $this->assertNotSame('', trim($hint));
        }
    }
}
