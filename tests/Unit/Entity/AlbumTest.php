<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Album;
use PHPUnit\Framework\TestCase;

class AlbumTest extends TestCase
{
    private Album $album;

    protected function setUp(): void
    {
        $this->album = new Album();
    }

    public function testNameIsStored(): void
    {
        $this->album->setName('Voyages 2026');

        $this->assertSame('Voyages 2026', $this->album->getName());
    }

    public function testNewAlbumHasEmptyMediaCollection(): void
    {
        $this->assertCount(0, $this->album->getMedias());
    }
}