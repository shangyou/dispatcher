<?php namespace Indatus\Dispatcher\Commands;

/**
 * @author Ben Kuhl <bkuhl@indatus.com>
 */

use Config;
use Mockery as m;
use ReflectionClass;
use TestCase;

class TestMake extends TestCase
{
    /**
     * @var \Indatus\Dispatcher\Commands\Make
     */
    private $command;

    public function setUp()
    {
        parent::setUp();

        $this->command = $this->makeFactory();
    }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testName()
    {
        $this->assertEquals('scheduled:make', $this->command->getName());
    }

    public function testDescription()
    {
        $this->assertEquals('Create a new scheduled artisan command', $this->command->getDescription());
    }

    public function testStubExists()
    {
        $this->assertFileExists($this->getStubPath('command.stub'));
    }

    public function testExtendStub()
    {
        //force visibility for testing
        $class = new ReflectionClass('Indatus\Dispatcher\Commands\Make');
        $method = $class->getMethod('extendStub');
        $method->setAccessible(true);

        $stubContents = file_get_contents($this->getStubPath('command.stub'));

        $replacements = [
            'use Illuminate\Console\Command' => "use Indatus\\Dispatcher\\Scheduling\\ScheduledCommand;\n".
                "use Indatus\\Dispatcher\\Scheduling\\Schedulable;\n".
                "use Indatus\\Dispatcher\\Drivers\\".ucwords(Config::get('dispatcher::driver'))."\\Scheduler",
            'extends Command {' => 'extends ScheduledCommand {',
            'parent::__construct();' => $stubContents,
        ];

        $delimeter = '*****';
        $extendedStub = $method->invoke($this->makeFactory(), implode($delimeter, array_keys($replacements)));

        $this->assertEquals(implode($delimeter, array_values($replacements)), $extendedStub);
    }

    private function getStubPath($filename)
    {
        return implode(DIRECTORY_SEPARATOR, [
                $this->getPackagePath(),
                'Commands',
                'stubs',
                $filename
            ]);
    }

    private function makeFactory()
    {
        return new Make(m::mock('Illuminate\Filesystem\Filesystem'));
    }
}
