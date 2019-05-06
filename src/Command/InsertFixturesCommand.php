<?php

namespace App\Command;

use App\Fixtures\AppFixtures;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Command line command for inserting development data fixtures.
 */
class InsertFixturesCommand extends Command
{
    /**
     * Database handler.
     * @var \PDO
     */
    private $dbh;

    /**
     * Application data fixtures.
     * @var AppFixtures
     */
    private $fixtures;

    /**
     * Current application environment.
     * @var string
     */
    private $environment;

    /**
     * Class constructor.
     */
    public function __construct(\PDO $dbh, AppFixtures $fixtures, string $environment)
    {
        $this->dbh = $dbh;
        $this->fixtures = $fixtures;
        $this->environment = $environment;

        parent::__construct();
    }

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setName('app:fixtures:insert')
            ->setDescription('Insert database fixtures.')
            ->setHelp('This command inserts demo data fixtures in the database.')
        ;
    }

    /**
     * Run the command, for example, bin/console app:generate-fixtures. It can
     * be executed only in development environment as a safety measure to not
     * delete something important.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ('dev' !== $this->environment) {
            $output->writeln('This command can be executed only in development.');

            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'This will erase entire database. Are you sure you want to continue? [y/N]',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Exiting...');

            return;
        }

        // Delete all existing categories and add new ones.
        $output->writeln('Deleting existing categories');
        $this->dbh->query('DELETE FROM bugdb_pseudo_packages');
        $output->writeln('Adding new categories');
        $this->fixtures->insertCategories();

        // Delete all current bug reports and add new ones.
        $output->writeln('Deleting existing bug reports');
        $this->dbh->query('DELETE FROM bugdb');
        $output->writeln('Adding new bug reports');
        $this->fixtures->insertBugs();

        // Delete all current reasons and add new ones.
        $output->writeln('Deleting existing reasons');
        $this->dbh->query('DELETE FROM bugdb_resolves');
        $output->writeln('Adding new reasons');
        $this->fixtures->insertReasons();
    }
}
