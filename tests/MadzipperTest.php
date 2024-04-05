<?php

namespace Madnest\Madzipper\Tests;

use Exception;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Madnest\Madzipper\Madzipper;
use Mockery;
use RuntimeException;

class MadzipperTest extends TestCase
{
    /**
     * @var \Madnest\Madzipper\Madzipper
     */
    public $archive;

    /**
     * @var \Mockery\Mock
     */
    public $file;

    public function setUp(): void
    {
        $this->file = Mockery::mock(new Filesystem());
        $this->archive = new Madzipper($this->file);
        $this->archive->make('foo', new \Madnest\Madzipper\Tests\ArrayArchive('foo', true));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    public function an_archive_can_be_made(): void
    {
        $this->assertSame(\Madnest\Madzipper\Tests\ArrayArchive::class, $this->archive->getArchiveType());
        $this->assertSame('foo', $this->archive->getFilePath());
    }

    /** @test */
    public function is_throws_an_exception_when_directory_could_not_be_created(): void
    {
        $path = getcwd().time();

        $this->file->shouldReceive('makeDirectory')
            ->with($path, 0755, true)
            ->andReturn(false);

        $zip = new Madzipper($this->file);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create folder');

        $zip->make($path.DIRECTORY_SEPARATOR.'createMe.zip');
    }

    /** @test */
    public function files_can_be_added_and_received(): void
    {
        $this->file->shouldReceive('isFile')->with('foo.bar')
            ->times(1)->andReturn(true);
        $this->file->shouldReceive('isFile')->with('foo')
            ->times(1)->andReturn(true);

        $this->archive->add('foo.bar');
        $this->archive->add('foo');

        $this->assertSame('foo', $this->archive->getFileContent('foo'));
        $this->assertSame('foo.bar', $this->archive->getFileContent('foo.bar'));
    }

    /** @test */
    public function files_can_be_added_and_received_as_array(): void
    {
        $this->file->shouldReceive('isFile')->with('foo.bar')
            ->times(1)->andReturn(true);
        $this->file->shouldReceive('isFile')->with('foo')
            ->times(1)->andReturn(true);

        /**Array**/
        $this->archive->add([
            'foo.bar',
            'foo',
        ]);

        $this->assertSame('foo', $this->archive->getFileContent('foo'));
        $this->assertSame('foo.bar', $this->archive->getFileContent('foo.bar'));
    }

    /** @test */
    public function files_can_be_added_and_received_as_custom_filename_array(): void
    {
        $this->file->shouldReceive('isFile')->with('foo.bar')
            ->times(1)->andReturn(true);
        $this->file->shouldReceive('isFile')->with('foo')
            ->times(1)->andReturn(true);

        /**Array**/
        $this->archive->add([
            'custom.bar' => 'foo.bar',
            'custom' => 'foo',
        ]);

        $this->assertSame('custom', $this->archive->getFileContent('custom'));
        $this->assertSame('custom.bar', $this->archive->getFileContent('custom.bar'));
    }

    /** @test */
    public function files_can_be_added_and_received_with_sub_folder(): void
    {
        /*
         * Add the local folder /path/to/fooDir as folder fooDir to the repository
         * and make sure the folder structure within the repository is there.
         */
        $this->file->shouldReceive('isFile')->with('/path/to/fooDir')
            ->once()->andReturn(false);

        $this->file->shouldReceive('files')->with('/path/to/fooDir')
            ->once()->andReturn(['fileInFooDir.bar', 'fileInFooDir.foo']);

        $this->file->shouldReceive('directories')->with('/path/to/fooDir')
            ->once()->andReturn(['fooSubdir']);

        $this->file->shouldReceive('files')->with('/path/to/fooDir/fooSubdir')
            ->once()->andReturn(['fileInFooDir.bar']);
        $this->file->shouldReceive('directories')->with('/path/to/fooDir/fooSubdir')
            ->once()->andReturn([]);

        $this->archive->folder('fooDir')
            ->add('/path/to/fooDir');

        $this->assertSame('fooDir/fileInFooDir.bar', $this->archive->getFileContent('fooDir/fileInFooDir.bar'));
        $this->assertSame('fooDir/fileInFooDir.foo', $this->archive->getFileContent('fooDir/fileInFooDir.foo'));
        $this->assertSame('fooDir/fooSubdir/fileInFooDir.bar', $this->archive->getFileContent('fooDir/fooSubdir/fileInFooDir.bar'));
    }

    /** @test */
    public function exception_is_thrown_when_accessing_content_that_does_not_exist(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The file "baz" cannot be found');

        $this->archive->getFileContent('baz');
    }

    /** @test */
    public function files_can_be_removed(): void
    {
        $this->file->shouldReceive('isFile')->with('foo')
            ->andReturn(true);

        $this->archive->add('foo');

        $this->assertTrue($this->archive->contains('foo'));

        $this->archive->remove('foo');

        $this->assertFalse($this->archive->contains('foo'));

        //----

        $this->file->shouldReceive('isFile')->with('foo')
            ->andReturn(true);
        $this->file->shouldReceive('isFile')->with('fooBar')
            ->andReturn(true);

        $this->archive->add(['foo', 'fooBar']);

        $this->assertTrue($this->archive->contains('foo'));
        $this->assertTrue($this->archive->contains('fooBar'));

        $this->archive->remove(['foo', 'fooBar']);

        $this->assertFalse($this->archive->contains('foo'));
        $this->assertFalse($this->archive->contains('fooBar'));
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function it_extracts_whitelisted(): void
    {
        $this->file
            ->shouldReceive('isFile')
            ->with('foo')
            ->andReturn(true);

        $this->file
            ->shouldReceive('isFile')
            ->with('foo.log')
            ->andReturn(true);

        $this->archive
            ->add('foo')
            ->add('foo.log');

        $this->file
            ->shouldReceive('put')
            ->with(realpath('').DIRECTORY_SEPARATOR.'foo', 'foo');

        $this->file
            ->shouldReceive('put')
            ->with(realpath('').DIRECTORY_SEPARATOR.'foo.log', 'foo.log');

        $this->archive
            ->extractTo(getcwd(), ['foo'], Madzipper::WHITELIST);
    }

    /** @test */
    public function extracting_throws_exception_when_it_could_not_create_directory(): void
    {
        $path = getcwd().time();

        $this->file
            ->shouldReceive('isFile')
            ->with('foo.log')
            ->andReturn(true);

        $this->file->shouldReceive('makeDirectory')
            ->with($path, 0755, true)
            ->andReturn(false);

        $this->archive->add('foo.log');

        $this->file->shouldNotReceive('put')
            ->with(realpath('').DIRECTORY_SEPARATOR.'foo.log', 'foo.log');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create folder');

        $this->archive
            ->extractTo($path, ['foo'], Madzipper::WHITELIST);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function it_extracts_whitelisted_from_sub_directory(): void
    {
        $this->file->shouldReceive('isFile')->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive
            ->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $this->file
            ->shouldReceive('put')
            ->with(realpath('').DIRECTORY_SEPARATOR.'baz', 'foo/bar/baz');

        $this->file
            ->shouldReceive('put')
            ->with(realpath('').DIRECTORY_SEPARATOR.'baz.log', 'foo/bar/baz.log');

        $this->archive
            ->extractTo(getcwd(), ['baz'], Madzipper::WHITELIST);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function it_extracts_whitelist_with_exact_matching(): void
    {
        $this->file->shouldReceive('isFile')->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive
            ->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $this->file
            ->shouldReceive('put')
            ->with(realpath('').DIRECTORY_SEPARATOR.'baz', 'foo/bar/baz');

        $this->archive
            ->extractTo(getcwd(), ['baz'], Madzipper::WHITELIST | Madzipper::EXACT_MATCH);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function it_extracts_whitelist_with_exact_matching_from_sub_directory(): void
    {
        $this->file->shouldReceive('isFile')->andReturn(true);
        $this->file->shouldReceive('exists')->andReturn(false);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive->folder('foo/bar/subDirectory')
            ->add('bazInSubDirectory')
            ->add('bazInSubDirectory.log');

        $this->archive->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $subDirectoryPath = realpath('').DIRECTORY_SEPARATOR.'subDirectory';
        $subDirectoryFilePath = $subDirectoryPath.'/bazInSubDirectory';
        $this->file->shouldReceive('put')
            ->with($subDirectoryFilePath, 'foo/bar/subDirectory/bazInSubDirectory');

        $this->archive
            ->extractTo(getcwd(), ['subDirectory/bazInSubDirectory'], Madzipper::WHITELIST | Madzipper::EXACT_MATCH);

        $this->file->shouldHaveReceived('makeDirectory')->with($subDirectoryPath, 0755, true, true);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function when_it_extracts_it_ignores_black_listed_files(): void
    {
        $this->file->shouldReceive('isFile')->with('foo')->andReturn(true);

        $this->file->shouldReceive('isFile')->with('bar')->andReturn(true);

        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive->add('foo')->add('bar');

        $this->file->shouldReceive('put')->with(realpath('').DIRECTORY_SEPARATOR.'foo', 'foo');
        $this->file->shouldNotReceive('put')->with(realpath('').DIRECTORY_SEPARATOR.'bar', 'bar');

        $this->archive->extractTo(getcwd(), ['bar'], Madzipper::BLACKLIST);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function when_it_extracts_it_ignores_black_listed_files_from_sub_directory(): void
    {
        $currentDir = getcwd();

        $this->file->shouldReceive('isFile')->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive->add('rootLevelFile');

        $this->archive->folder('foo/bar/sub')
            ->add('fileInSubSubDir');

        $this->archive->folder('foo/bar')
            ->add('fileInSubDir')
            ->add('fileBlackListedInSubDir');

        $this->file->shouldReceive('put')->with($currentDir.DIRECTORY_SEPARATOR.'fileInSubDir', 'foo/bar/fileInSubDir');
        $this->file->shouldReceive('put')->with($currentDir.DIRECTORY_SEPARATOR.'sub/fileInSubSubDir', 'foo/bar/sub/fileInSubSubDir');

        $this->file->shouldNotReceive('put')->with($currentDir.DIRECTORY_SEPARATOR.'fileBlackListedInSubDir', 'fileBlackListedInSubDir');
        $this->file->shouldNotReceive('put')->with($currentDir.DIRECTORY_SEPARATOR.'rootLevelFile', 'rootLevelFile');

        $this->archive->extractTo($currentDir, ['fileBlackListedInSubDir'], Madzipper::BLACKLIST);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function when_it_extracts_it_ignores_black_listed_files_from_sub_directory_with_exact_matching(): void
    {
        $this->file->shouldReceive('isFile')->with('baz')
            ->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->file->shouldReceive('isFile')->with('baz.log')
            ->andReturn(true);

        $this->archive->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $this->file->shouldReceive('put')->with(realpath('').DIRECTORY_SEPARATOR.'baz.log', 'foo/bar/baz.log');

        $this->archive->extractTo(getcwd(), ['baz'], Madzipper::BLACKLIST | Madzipper::EXACT_MATCH);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     * */
    public function is_extracts_matching_regex_from_sub_folder(): void
    {
        $this->file->shouldReceive('isFile')->with('baz')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('baz.log')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('subFolderFileToIgnore')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('subFolderFileToExtract.log')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('rootLevelMustBeIgnored.log')->andReturn(true);

        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive->add('rootLevelMustBeIgnored.log');

        $this->archive->folder('foo/bar/subFolder')
            ->add('subFolderFileToIgnore')
            ->add('subFolderFileToExtract.log');

        $this->archive->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $this->file->shouldReceive('put')->with(realpath('').DIRECTORY_SEPARATOR.'baz.log', 'foo/bar/baz.log');
        $this->file->shouldReceive('put')->with(realpath('').DIRECTORY_SEPARATOR.'subFolder/subFolderFileToExtract.log', 'foo/bar/subFolder/subFolderFileToExtract.log');
        $this->file->shouldNotReceive('put')->with(realpath('').DIRECTORY_SEPARATOR.'rootLevelMustBeIgnored.log', 'rootLevelMustBeIgnored.log');
        $this->file->shouldNotReceive('put')->with(realpath('').DIRECTORY_SEPARATOR.'baz', 'foo/bar/baz');
        $this->file->shouldNotReceive('put')->with(realpath('').DIRECTORY_SEPARATOR.'subFolder/subFolderFileToIgnore', 'foo/bar/subFolder/subFolderFileToIgnore');

        $this->archive->extractMatchingRegex(getcwd(), '/\.log$/i');
    }

    /** @test */
    public function it_throws_an_exception_when_extracting_matching_regex_when_regex_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing pass valid regex parameter');
        $this->archive->extractMatchingRegex(getcwd(), '');
    }

    /** @test */
    public function is_can_add_files_to_folders_and_to_home(): void
    {
        $this->archive->folder('foo/bar');
        $this->assertSame('foo/bar', $this->archive->getCurrentFolderPath());

        //----

        $this->file->shouldReceive('isFile')->with('foo')->andReturn(true);

        $this->archive->add('foo');
        $this->assertSame('foo/bar/foo', $this->archive->getFileContent('foo/bar/foo'));

        //----

        $this->file->shouldReceive('isFile')->with('bar')->andReturn(true);

        $this->archive->home()->add('bar');
        $this->assertSame('bar', $this->archive->getFileContent('bar'));

        //----

        $this->file->shouldReceive('isFile')->with('baz/bar/bing')->andReturn(true);

        $this->archive->folder('test')->add('baz/bar/bing');
        $this->assertSame('test/bing', $this->archive->getFileContent('test/bing'));
    }

    /** @test */
    public function is_can_list_files(): void
    {
        // testing empty file
        $this->file->shouldReceive('isFile')->with('foo.file')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('bar.file')->andReturn(true);

        $this->assertSame([], $this->archive->listFiles());

        // testing not empty file
        $this->archive->add('foo.file');
        $this->archive->add('bar.file');

        $this->assertSame(['foo.file', 'bar.file'], $this->archive->listFiles());

        // testing with a empty sub dir
        $this->file->shouldReceive('isFile')->with('/path/to/subDirEmpty')->andReturn(false);

        $this->file->shouldReceive('files')->with('/path/to/subDirEmpty')->andReturn([]);
        $this->file->shouldReceive('directories')->with('/path/to/subDirEmpty')->andReturn([]);
        $this->archive->folder('subDirEmpty')->add('/path/to/subDirEmpty');

        $this->assertSame(['foo.file', 'bar.file'], $this->archive->listFiles());

        // testing with a not empty sub dir
        $this->file->shouldReceive('isFile')->with('/path/to/subDir')->andReturn(false);
        $this->file->shouldReceive('isFile')->with('sub.file')->andReturn(true);

        $this->file->shouldReceive('files')->with('/path/to/subDir')->andReturn(['sub.file']);
        $this->file->shouldReceive('directories')->with('/path/to/subDir')->andReturn([]);

        $this->archive->folder('subDir')->add('/path/to/subDir');

        $this->assertSame(['foo.file', 'bar.file', 'subDir/sub.file'], $this->archive->listFiles());
    }

    /** @test */
    public function is_can_list_files_with_regex_filter(): void
    {
        // add 2 files to root level in zip
        $this->file->shouldReceive('isFile')->with('foo.file')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('bar.log')->andReturn(true);

        $this->archive
            ->add('foo.file')
            ->add('bar.log');

        // add sub directory with 2 files inside
        $this->file->shouldReceive('isFile')->with('/path/to/subDir')->andReturn(false);
        $this->file->shouldReceive('isFile')->with('sub.file')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('anotherSub.log')->andReturn(true);

        $this->file->shouldReceive('files')->with('/path/to/subDir')->andReturn(['sub.file', 'anotherSub.log']);
        $this->file->shouldReceive('directories')->with('/path/to/subDir')->andReturn([]);

        $this->archive->folder('subDir')->add('/path/to/subDir');

        $this->assertSame(
            ['foo.file', 'subDir/sub.file'],
            $this->archive->listFiles('/\.file$/i') // filter out files ending with ".file" pattern
        );
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_list_files_with_invalid_regex_filter(): void
    {
        $this->file->shouldReceive('isFile')->with('foo.file')->andReturn(true);
        $this->archive->add('foo.file');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Regular expression match on \'foo.file\' failed with error. Please check if pattern is valid regular expression.');

        $invalidPattern = 'asdasd';
        $this->archive->listFiles($invalidPattern);
    }
}
