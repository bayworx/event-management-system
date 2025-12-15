<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test email configuration by sending a test email',
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('recipient', InputArgument::REQUIRED, 'Email address to send test email to')
            ->setHelp('This command allows you to test your email configuration by sending a test email.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipient = $input->getArgument('recipient');

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $io->error('Invalid email address provided.');
            return Command::FAILURE;
        }

        $io->title('Email Configuration Test');
        $io->text('Attempting to send test email...');

        try {
            $email = (new Email())
                ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@example.com')
                ->to($recipient)
                ->subject('Test Email - Event Management System')
                ->html($this->getTestEmailHtml());

            $this->mailer->send($email);

            $io->success([
                'Test email sent successfully!',
                sprintf('Sent to: %s', $recipient),
                sprintf('From: %s', $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@example.com'),
            ]);

            $io->note('If you don\'t receive the email, check:');
            $io->listing([
                'Spam/junk folder',
                'MAILER_DSN configuration in .env',
                'Gmail App Password is correct',
                'Firewall allows outbound SMTP (port 587/465)',
                'Application logs: var/log/prod.log',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'Failed to send test email!',
                'Error: ' . $e->getMessage(),
            ]);

            $io->note('Common issues:');
            $io->listing([
                'Invalid MAILER_DSN format in .env',
                'Wrong Gmail App Password',
                '2FA not enabled on Gmail account',
                'SMTP ports blocked by firewall',
                'Missing Composer packages',
            ]);

            return Command::FAILURE;
        }
    }

    private function getTestEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border: 1px solid #e9ecef;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background: #e9ecef;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-radius: 0 0 8px 8px;
        }
        .info-list {
            background: #fff;
            padding: 15px;
            border-left: 4px solid #0d6efd;
            margin: 20px 0;
        }
        .info-list li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">✅ Email Configuration Test</h1>
    </div>
    
    <div class="content">
        <div class="success">
            <strong>Success!</strong> Your email configuration is working correctly.
        </div>
        
        <p>This is a test email from your <strong>Event Management System</strong>.</p>
        
        <p>If you received this email, it means:</p>
        
        <div class="info-list">
            <ul>
                <li>✅ Your SMTP configuration is correct</li>
                <li>✅ Gmail integration is working</li>
                <li>✅ Emails can be sent from your application</li>
                <li>✅ The mailer service is properly configured</li>
            </ul>
        </div>
        
        <p><strong>What's next?</strong></p>
        <p>Your Event Management System is now ready to send:</p>
        <ul>
            <li>Event registration verification emails</li>
            <li>Password reset emails</li>
            <li>Attendee login links</li>
            <li>Admin notifications</li>
        </ul>
        
        <p>You can now use the system with confidence that emails will be delivered.</p>
    </div>
    
    <div class="footer">
        <p><strong>Event Management System</strong></p>
        <p>This is an automated test email.</p>
        <p>&copy; 2025 All rights reserved.</p>
    </div>
</body>
</html>
HTML;
    }
}
