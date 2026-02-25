<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[AsCommand(
    name: 'app:test-email',
    description: 'Envoyer un email de test à Mailtrap',
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer,
        string $mailerFrom = 'noreply@mindforge.com'
    ) {
        parent::__construct();
        $this->mailerFrom = $mailerFrom;
    }

    private string $mailerFrom;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $email = (new TemplatedEmail())
                ->from($this->mailerFrom)
                ->to('youssefzawali000@gmail.com')
                ->subject('🧪 Email de Test - MindForge')
                ->text('Ceci est un email de test');

            $this->mailer->send($email);
            $io->success('✅ Email de test envoyé avec succès via Gmail !');
            $io->note('Vérifiez youssefzawali000@gmail.com (aussi dans Spam)');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'envoi : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
