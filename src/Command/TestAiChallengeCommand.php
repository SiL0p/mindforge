<?php

namespace App\Command;

use App\Service\Community\AiChallengeGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-ai-challenge',
    description: 'Test the AI Challenge Generator service',
)]
class TestAiChallengeCommand extends Command
{
    public function __construct(
        private AiChallengeGeneratorService $aiService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('category', InputArgument::OPTIONAL, 'Category (tech_skills, soft_skills, physical, creative)', 'tech_skills');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $input->getArgument('category');
        $output->writeln("Testing AI Challenge Generator with category: <info>$category</info>");

        try {
            $result = $this->aiService->generateChallenge($category);
            $output->writeln("\n<info>Success!</info>");
            $output->writeln("Title: " . $result['title']);
            $output->writeln("Description: " . $result['description']);
            $output->writeln("Difficulty: " . $result['difficulty']);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("\n<error>Error:</error>");
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }
    }
}
