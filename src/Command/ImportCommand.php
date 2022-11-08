<?php

namespace App\Command;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import',
    description: 'Import data from csv to neo4j.',
    hidden: false
)]
class ImportCommand extends Command
{
    public function __construct(
        protected ClientInterface $client,
        #[Autowire('%app.csv_import_dir%')]
        protected string $csvImportDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createIndexes($output);
        $this->importCpu($output);
        $this->importComputer($output);
        $this->importMotherboard($output);
        $this->importGraphicsCard($output);
        $this->importEthernet($output);
        $this->importPrinter($output);

        return Command::SUCCESS;
    }

    protected function createIndexes(OutputInterface $output): void
    {
        $output->writeln('Creating indexes...');
        $this->client->runStatements([
            Statement::create('CREATE CONSTRAINT IF NOT EXISTS FOR (c:Cpu) REQUIRE c.id IS UNIQUE'),
            Statement::create('CREATE CONSTRAINT IF NOT EXISTS FOR (c:Computer) REQUIRE c.id IS UNIQUE'),
            Statement::create('CREATE CONSTRAINT IF NOT EXISTS FOR (m:Motherboard) REQUIRE m.id IS UNIQUE'),
            Statement::create('CREATE CONSTRAINT IF NOT EXISTS FOR (e:EthernetPciCard) REQUIRE e.id IS UNIQUE'),
            Statement::create('CREATE CONSTRAINT IF NOT EXISTS FOR (g:GraphicsCard) REQUIRE g.id IS UNIQUE'),
            Statement::create('CREATE CONSTRAINT IF NOT EXISTS FOR (p:Printer) REQUIRE p.id IS UNIQUE'),
            Statement::create('CREATE CONSTRAINT IF NOT EXISTS FOR (p:Probe) REQUIRE p.id IS UNIQUE'),
            Statement::create('CREATE FULLTEXT INDEX searchCpu IF NOT EXISTS FOR (c:Cpu) ON EACH [c.vendor, c.model]'),
            Statement::create('CREATE FULLTEXT INDEX searchComputer IF NOT EXISTS FOR (c:Computer) ON EACH [c.type, c.vendor, c.model]'),
            Statement::create('CREATE FULLTEXT INDEX searchMotherboard IF NOT EXISTS FOR (m:Motherboard) ON EACH [m.manufacturer, m.productName, m.version]'),
            Statement::create('CREATE FULLTEXT INDEX searchEthernetPciCard IF NOT EXISTS FOR (e:EthernetPciCard) ON EACH [e.vendor, e.subVendor, e.device]'),
            Statement::create('CREATE FULLTEXT INDEX searchGraphicsCard IF NOT EXISTS FOR (g:GraphicsCard) ON EACH [g.vendor, g.subVendor, g.device]'),
            Statement::create('CREATE FULLTEXT INDEX searchPrinter IF NOT EXISTS FOR (p:Printer) ON EACH [p.vendor, p.device]'),
        ]);
        $output->writeln('<info>Finished creating indexes!</info>');
    }

    protected function importCpu(OutputInterface $output): void
    {
        $output->writeln('Importing cpus...');
        $this->client->run(
            <<<CYPHER
            LOAD CSV WITH HEADERS FROM "file:///cpu.csv" AS row
            WITH row
            MERGE (cpu:Cpu {id: row.cpuId})
            ON CREATE SET cpu += {vendor: row.vendor, model: row.model}
            MERGE (probe:Probe {id: row.probeId})
            MERGE (probe)-[:HAS_CPU]->(cpu)
            CYPHER
        );
        $output->writeln('<info>Finished importing cpus!</info>');
    }

    protected function importComputer(OutputInterface $output): void
    {
        $output->writeln('Importing computers...');
        $this->client->run(
            <<<CYPHER
            LOAD CSV WITH HEADERS FROM "file:///computer.csv" AS row
            WITH row
            MERGE (computer:Computer {id: row.computerId})
            ON CREATE SET computer += {type: row.type, vendor: row.vendor, model: row.model}
            MERGE (probe:Probe {id: row.probeId})
            MERGE (probe)-[:HAS_COMPUTER]->(computer)
            CYPHER
        );
        $output->writeln('<info>Finished importing computers!</info>');
    }

    protected function importMotherboard(OutputInterface $output): void
    {
        $output->writeln('Importing motherboards...');
        $this->client->run(
            <<<CYPHER
            LOAD CSV WITH HEADERS FROM "file:///motherboard.csv" AS row
            WITH row
            MERGE (motherboard:Motherboard {id: row.motherboardId})
            ON CREATE SET motherboard += {manufacturer: row.manufacturer, productName: row.productName, version: row.version}
            MERGE (computer:Computer {id: row.computerId})
            MERGE (computer)-[:HAS_MOTHERBOARD]->(motherboard)
            CYPHER
        );
        $output->writeln('<info>Finished importing motherboards!</info>');
    }

    protected function importGraphicsCard(OutputInterface $output): void
    {
        $output->writeln('Importing graphics cards...');
        $this->client->run(
            <<<CYPHER
            LOAD CSV WITH HEADERS FROM "file:///gpu.csv" AS row
            WITH row
            MERGE (gpu:GraphicsCard {id: row.gpuId})
            ON CREATE SET gpu += {vendor: row.vendor, subVendor: row.subVendor, device: row.device}
            MERGE (probe:Probe {id: row.probeId})
            MERGE (probe)-[:HAS_GPU]->(gpu)
            CYPHER
        );
        $output->writeln('<info>Finished importing graphics cards!</info>');
    }

    protected function importEthernet(OutputInterface $output): void
    {
        $output->writeln('Importing ethernet pci cards...');
        $this->client->run(
            <<<CYPHER
            LOAD CSV WITH HEADERS FROM "file:///ethernet.csv" AS row
            WITH row
            MERGE (ethernet:EthernetPciCard {id: row.ethernetId})
            ON CREATE SET ethernet += {vendor: row.vendor, subVendor: row.subVendor, device: row.device}
            MERGE (probe:Probe {id: row.probeId})
            MERGE (probe)-[:HAS_ETHERNET_PCI]->(ethernet)
            CYPHER
        );
        $output->writeln('<info>Finished importing ethernet pci cards!</info>');
    }

    protected function importPrinter(OutputInterface $output): void
    {
        $output->writeln('Importing printers...');
        $this->client->run(
            <<<CYPHER
            LOAD CSV WITH HEADERS FROM "file:///printer.csv" AS row
            WITH row
            MERGE (printer:Printer {id: row.printerId})
            ON CREATE SET printer += {vendor: row.vendor, device: row.device}
            CYPHER
        );
        $output->writeln('<info>Finished importing printers!</info>');
    }
}
