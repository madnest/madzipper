<?php

namespace Madnest\Madzipper\Tests\Repositories;

use Exception;
use Madnest\Madzipper\Repositories\ZipRepository;
use Madnest\Madzipper\Tests\TestCase;
use Mockery;
use ZipArchive;

class ZipRepositoryTest extends TestCase
{
    /**
     * @var ZipRepository
     */
    public $zip;

    /**
     * @var \Mockery\Mock
     */
    public $mock;

    public function setUp(): void
    {
        $this->mock = Mockery::mock(new ZipArchive());
        $this->zip = new ZipRepository('foo', true, $this->mock);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    public function a_zip_repository_can_be_made()
    {
        $zip = new ZipRepository('foo.zip', true);
        $this->assertFalse($zip->fileExists('foo'));
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_open_non_existing_zip()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error: Failed to open idonotexist.zip! Error: ZipArchive::ER_');
        new ZipRepository('idonotexist.zip', false);
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_open_something_else_than_a_zip()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Error: Failed to open (.*)ZipRepositoryTest.php! Error: ZipArchive::ER_NOZIP - Not a zip archive./');
        new ZipRepository(__DIR__.DIRECTORY_SEPARATOR.'ZipRepositoryTest.php', false);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function it_can_add_files()
    {
        $this->mock->shouldReceive('addFile')->once()->with('bar', 'bar');
        $this->mock->shouldReceive('addFile')->once()->with('bar', 'foo/bar');
        $this->mock->shouldReceive('addFile')->once()->with('foo/bar', 'bar');

        $this->zip->addFile('bar', 'bar');
        $this->zip->addFile('bar', 'foo/bar');
        $this->zip->addFile('foo/bar', 'bar');
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function it_can_remove_files()
    {
        $this->mock->shouldReceive('deleteName')->once()->with('bar');
        $this->mock->shouldReceive('deleteName')->once()->with('foo/bar');

        $this->zip->removeFile('bar');
        $this->zip->removeFile('foo/bar');
    }

    /** @test */
    public function it_can_get_file_content()
    {
        $this->mock->shouldReceive('getFromName')->once()
            ->with('bar')->andReturn('foo');
        $this->mock->shouldReceive('getFromName')->once()
            ->with('foo/bar')->andReturn('baz');

        $this->assertSame('foo', $this->zip->getFileContent('bar'));
        $this->assertSame('baz', $this->zip->getFileContent('foo/bar'));
    }

    /** @test */
    public function is_can_get_file_stream()
    {
        $this->mock->shouldReceive('getStream')->once()
            ->with('bar')->andReturn('foo');
        $this->mock->shouldReceive('getStream')->once()
            ->with('foo/bar')->andReturn('baz');

        $this->assertSame('foo', $this->zip->getFileStream('bar'));
        $this->assertSame('baz', $this->zip->getFileStream('foo/bar'));
    }

    /** @test */
    public function it_can_tell_wether_file_exists()
    {
        $this->mock->shouldReceive('locateName')->once()
            ->with('bar')->andReturn(true);
        $this->mock->shouldReceive('locateName')->once()
            ->with('foo/bar')->andReturn(false);

        $this->assertTrue($this->zip->fileExists('bar'));
        $this->assertFalse($this->zip->fileExists('foo/bar'));
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function it_can_close()
    {
        $this->zip->close();
    }
}
