<?php

namespace App\Tests\Command;

use Laudis\Neo4j\Exception\PropertyDoesNotExistException;

class ImportCommandTest extends CommandTestCase
{
    protected function getCommand(): string
    {
        return 'app:import';
    }

    protected function assertOutput(string $output): void
    {
        $this->assertStringContainsString('Finished creating indexes!', $output);
        $this->assertStringContainsString('Finished importing cpus!', $output);
        $this->assertStringContainsString('Finished importing computers!', $output);
        $this->assertStringContainsString('Finished importing motherboards!', $output);
        $this->assertStringContainsString('Finished importing graphics cards!', $output);
        $this->assertStringContainsString('Finished importing ethernet pci cards!', $output);
        $this->assertStringContainsString('Finished importing printers!', $output);
    }

    protected function assertGraph(): void
    {
        foreach ($this->loadCsv('cpu.csv') as $cpu) {
            $this->assertCpuNode($cpu);
            $this->assertProbeCpuRelationship($cpu);
        }
        foreach ($this->loadCsv('computer.csv') as $computer) {
            $this->assertComputerNode($computer);
            $this->assertProbeComputerRelationship($computer);
        }
        foreach ($this->loadCsv('motherboard.csv') as $motherboard) {
            $this->assertMotherboardNode($motherboard);
            $this->assertComputerMotherboardRelationship($motherboard);
        }
        foreach ($this->loadCsv('gpu.csv') as $gpu) {
            $this->assertGraphicsCardNode($gpu);
            $this->assertProbeGraphicsCardRelationship($gpu);
        }
        foreach ($this->loadCsv('ethernet.csv') as $ethernet) {
            $this->assertEthernetPciCardNode($ethernet);
            $this->assertProbeEthernetPciCardRelationship($ethernet);
        }
        foreach ($this->loadCsv('printer.csv') as $printer) {
            $this->assertPrinterNode($printer);
        }
    }

    protected function assertCpuNode(array $data): void
    {
        $result = $this->client->run('MATCH (cpu:Cpu {id: $id}) RETURN cpu', ['id' => $data['cpuId']])->first();
        $cpu = $result->get('cpu');
        $this->assertSame($data['vendor'], $cpu->getProperty('vendor'));
        $this->assertSame($data['model'], $cpu->getProperty('model'));
    }

    protected function assertProbeCpuRelationship(array $data): void
    {
        $result = $this->client->run(
            'MATCH (cpu:Cpu {id: $cpuId}) MATCH (probe:Probe {id: $probeId}) RETURN exists((probe)-[:HAS_CPU]->(cpu)) as hasRelationship',
            ['cpuId' => $data['cpuId'], 'probeId' => $data['probeId']]
        )->first();
        $this->assertTrue($result->get('hasRelationship'));
    }

    protected function assertComputerNode(array $data): void
    {
        $result = $this->client->run('MATCH (computer:Computer {id: $id}) RETURN computer', ['id' => $data['computerId']])->first();
        $computer = $result->get('computer');
        $this->assertSame($data['type'], $computer->getProperty('type'));
        $this->assertSame($data['vendor'], $computer->getProperty('vendor'));
        $this->assertSame($data['model'], $computer->getProperty('model'));
    }

    protected function assertProbeComputerRelationship(array $data): void
    {
        $result = $this->client->run(
            'MATCH (computer:Computer {id: $computerId}) MATCH (probe:Probe {id: $probeId}) RETURN exists((probe)-[:HAS_COMPUTER]->(computer)) as hasRelationship',
            ['computerId' => $data['computerId'], 'probeId' => $data['probeId']]
        )->first();
        $this->assertTrue($result->get('hasRelationship'));
    }

    protected function assertMotherboardNode(array $data): void
    {
        $result = $this->client->run('MATCH (motherboard:Motherboard {id: $id}) RETURN motherboard', ['id' => $data['motherboardId']])->first();
        $motherboard = $result->get('motherboard');
        $this->assertSame($data['manufacturer'], $motherboard->getProperty('manufacturer'));
        $this->assertSame($data['productName'], $motherboard->getProperty('productName'));
        if (empty($data['version'])) {
            $this->expectException(PropertyDoesNotExistException::class);
            $this->expectExceptionMessage('Property "version" does not exist on node');
        }
        $this->assertSame($data['version'], $motherboard->getProperty('version'));
    }

    protected function assertComputerMotherboardRelationship(array $data): void
    {
        $result = $this->client->run(
            'MATCH (computer:Computer {id: $computerId}) MATCH (motherboard:Motherboard {id: $motherboardId}) RETURN exists((computer)-[:HAS_MOTHERBOARD]->(motherboard)) as hasRelationship',
            ['computerId' => $data['computerId'], 'motherboardId' => $data['motherboardId']]
        )->first();
        $this->assertTrue($result->get('hasRelationship'));
    }

    protected function assertGraphicsCardNode(array $data): void
    {
        $result = $this->client->run('MATCH (gpu:GraphicsCard {id: $id}) RETURN gpu', ['id' => $data['gpuId']])->first();
        $gpu = $result->get('gpu');
        $this->assertSame($data['vendor'], $gpu->getProperty('vendor'));
        $this->assertSame($data['device'], $gpu->getProperty('device'));
        $this->assertSame($data['subVendor'], $gpu->getProperty('subVendor'));
    }

    protected function assertEthernetPciCardNode(array $data): void
    {
        $result = $this->client->run('MATCH (ethernet:EthernetPciCard {id: $id}) RETURN ethernet', ['id' => $data['ethernetId']])->first();
        $ethernet = $result->get('ethernet');
        $this->assertSame($data['vendor'], $ethernet->getProperty('vendor'));
        $this->assertSame($data['device'], $ethernet->getProperty('device'));
        $this->assertSame($data['subVendor'], $ethernet->getProperty('subVendor'));
    }

    protected function assertPrinterNode(array $data): void
    {
        $result = $this->client->run('MATCH (printer:Printer {id: $id}) RETURN printer', ['id' => $data['printerId']])->first();
        $printer = $result->get('printer');
        $this->assertSame($data['vendor'], $printer->getProperty('vendor'));
        $this->assertSame($data['device'], $printer->getProperty('device'));
    }

    protected function assertProbeGraphicsCardRelationship(array $data): void
    {
        $result = $this->client->run(
            'MATCH (gpu:GraphicsCard {id: $gpuId}) MATCH (probe:Probe {id: $probeId}) RETURN exists((probe)-[:HAS_GPU]->(gpu)) as hasRelationship',
            ['probeId' => $data['probeId'], 'gpuId' => $data['gpuId']]
        )->first();
        $this->assertTrue($result->get('hasRelationship'));
    }

    protected function assertProbeEthernetPciCardRelationship(array $data): void
    {
        $result = $this->client->run(
            'MATCH (ethernet:EthernetPciCard {id: $ethernetId}) MATCH (probe:Probe {id: $probeId}) RETURN exists((probe)-[:HAS_ETHERNET_PCI]->(ethernet)) as hasRelationship',
            ['probeId' => $data['probeId'], 'ethernetId' => $data['ethernetId']]
        )->first();
        $this->assertTrue($result->get('hasRelationship'));
    }
}
