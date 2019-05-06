<?php declare(strict_types=1);

/**
 * Test for insert fixtures command.
 */

namespace App\Tests\Command;

use App\Command\InsertFixturesCommand;
use App\Fixtures\AppFixtures;
use Faker\Factory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use PHPUnit\Framework\TestCase;

class InsertFixturesCommandTest extends TestCase
{
    private $dbh;

    public function setUp(): void
    {
        $this->dbh = new \PDO('sqlite::memory:');
    }

    public function testExecute(): void
    {
        $insertFixturesCommand = new InsertFixturesCommand(
            $this->dbh,
            new AppFixtures($this->dbh, Factory::create(), []),
            'dev'
        );

        $application = new Application();
        $application->add($insertFixturesCommand);

        $command = $application->find('app:fixtures:insert');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['n']);
        $exitCode = $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/.../', $commandTester->getDisplay());
        $this->assertSame(0, $exitCode);
    }

    public function testExecuteInProdEnv(): void
    {
        $insertFixturesCommand = new InsertFixturesCommand(
            $this->dbh,
            new AppFixtures($this->dbh, Factory::create(), []),
            'prod'
        );

        $application = new Application();
        $application->add($insertFixturesCommand);

        $command = $application->find('app:fixtures:insert');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['n']);
        $exitCode = $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/This command can be executed only in dev/', $commandTester->getDisplay());
        $this->assertSame(0, $exitCode);
    }
}
