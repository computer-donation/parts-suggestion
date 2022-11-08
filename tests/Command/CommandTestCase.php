<?php

namespace App\Tests\Command;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends KernelTestCase
{
    protected ?ClientInterface $client = null;
    protected string $csvImportDir;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::$kernel->getContainer();
        $this->client = $container->get('test.'.ClientInterface::class);
        $this->csvImportDir = $container->getParameter('app.csv_import_dir');

        $this->initGraphDatabase();
    }

    public function testExecute()
    {
        $application = new Application(self::$kernel);

        $command = $application->find($this->getCommand());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $this->assertOutput($commandTester->getDisplay());
        $this->assertGraph();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->client = null;
    }

    protected function initGraphDatabase(): void
    {
        $this->client->runStatements([
            Statement::create('MATCH (n) DETACH DELETE n', []),
            // Statement::create('CALL apoc.schema.assert({},{},true) YIELD label, key RETURN *', []),
        ]);
    }

    abstract protected function getCommand(): string;

    abstract protected function assertOutput(string $output): void;

    abstract protected function assertGraph(): void;

    protected function loadCsv(string $fileName): array
    {
        $rows = [];
        $file = fopen($this->csvImportDir.DIRECTORY_SEPARATOR.$fileName, 'r');
        $header = fgetcsv($file);

        while (false !== ($row = fgetcsv($file))) {
            $rows[] = array_combine($header, $row);
        }

        fclose($file);

        return $rows;
    }
}
