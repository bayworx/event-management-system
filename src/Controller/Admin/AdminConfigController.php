<?php

namespace App\Controller\Admin;

use App\Form\AppPreferencesType;
use App\Form\CompanyInfoType;
use App\Form\EmailSettingsType;
use App\Form\FooterSettingsType;
use App\Service\ApplicationLogger;
use App\Service\ConfigurationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/config')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminConfigController extends AbstractController
{
    public function __construct(
        private ConfigurationService $configService,
        private SluggerInterface $slugger,
        private ApplicationLogger $appLogger,
        private MailerInterface $mailer
    ) {
    }

    #[Route('/', name: 'admin_config_index', methods: ['GET'])]
    public function index(): Response
    {
        // Initialize defaults if needed
        $this->configService->initializeDefaults();
        
        $configurations = $this->configService->getAllGrouped();
        
        return $this->render('admin/config/index.html.twig', [
            'configurations' => $configurations,
        ]);
    }

    #[Route('/company', name: 'admin_config_company', methods: ['GET', 'POST'])]
    public function company(Request $request): Response
    {
        $companyInfo = $this->configService->getCompanyInfo();
        
        $form = $this->createForm(CompanyInfoType::class, [
            'company_name' => $companyInfo['name'],
            'company_description' => $companyInfo['description'],
            'company_address' => $companyInfo['address'],
            'company_phone' => $companyInfo['phone'],
            'company_email' => $companyInfo['email'],
            'company_website' => $companyInfo['website'],
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Handle logo upload
            $logoFile = $form->get('company_logo')->getData();
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();
                
                // Create logos directory if it doesn't exist
                $uploadsDir = $this->getParameter('uploads_directory');
                $logosDir = $uploadsDir . '/logos';
                
                if (!is_dir($logosDir)) {
                    mkdir($logosDir, 0755, true);
                }

                try {
                    $logoFile->move($logosDir, $newFilename);
                    
                    // Store relative path for web access
                    $this->configService->set('company.logo', 'logos/' . $newFilename);
                    
                    $this->addFlash('success', 'Logo uploaded successfully.');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading logo file: ' . $e->getMessage());
                }
            }
            
            // Prepare changes for audit log
            $changes = [
                'company.name' => ['old' => $companyInfo['name'], 'new' => $data['company_name']],
                'company.description' => ['old' => $companyInfo['description'], 'new' => $data['company_description']],
                'company.address' => ['old' => $companyInfo['address'], 'new' => $data['company_address']],
                'company.phone' => ['old' => $companyInfo['phone'], 'new' => $data['company_phone']],
                'company.email' => ['old' => $companyInfo['email'], 'new' => $data['company_email']],
                'company.website' => ['old' => $companyInfo['website'], 'new' => $data['company_website']],
            ];
            
            // Filter out unchanged values
            $actualChanges = array_filter($changes, fn($change) => $change['old'] !== $change['new']);
            
            // Update configuration values
            $this->configService->updateMultiple([
                'company.name' => $data['company_name'],
                'company.description' => $data['company_description'],
                'company.address' => $data['company_address'],
                'company.phone' => $data['company_phone'],
                'company.email' => $data['company_email'],
                'company.website' => $data['company_website'],
            ]);
            
            // Log audit event
            $this->appLogger->logAuditEvent(
                'company_configuration_updated',
                $this->getUser(),
                $actualChanges
            );
            
            $this->addFlash('success', 'Company information updated successfully.');
            return $this->redirectToRoute('admin_config_company');
        }
        
        return $this->render('admin/config/company.html.twig', [
            'form' => $form->createView(),
            'current_logo' => $companyInfo['logo'],
        ]);
    }

    #[Route('/application', name: 'admin_config_application', methods: ['GET', 'POST'])]
    public function application(Request $request): Response
    {
        $appPreferences = $this->configService->getAppPreferences();
        
        $form = $this->createForm(AppPreferencesType::class, [
            'app_timezone' => $appPreferences['timezone'],
            'app_date_format' => $appPreferences['date_format'],
            'app_time_format' => $appPreferences['time_format'],
            'app_items_per_page' => (int) $appPreferences['items_per_page'],
            'app_theme' => $appPreferences['theme'],
            'app_maintenance_mode' => (bool) $appPreferences['maintenance_mode'],
            'app_version' => $appPreferences['version'],
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Prepare changes for audit log
            $changes = [
                'app.timezone' => ['old' => $appPreferences['timezone'], 'new' => $data['app_timezone']],
                'app.date_format' => ['old' => $appPreferences['date_format'], 'new' => $data['app_date_format']],
                'app.time_format' => ['old' => $appPreferences['time_format'], 'new' => $data['app_time_format']],
                'app.items_per_page' => ['old' => (int)$appPreferences['items_per_page'], 'new' => $data['app_items_per_page']],
                'app.theme' => ['old' => $appPreferences['theme'], 'new' => $data['app_theme']],
                'app.maintenance_mode' => ['old' => (bool)$appPreferences['maintenance_mode'], 'new' => (bool)($data['app_maintenance_mode'] ?? false)],
                'app.version' => ['old' => $appPreferences['version'], 'new' => $data['app_version']],
            ];
            
            // Filter out unchanged values
            $actualChanges = array_filter($changes, fn($change) => $change['old'] !== $change['new']);
            
            $this->configService->updateMultiple([
                'app.timezone' => $data['app_timezone'],
                'app.date_format' => $data['app_date_format'],
                'app.time_format' => $data['app_time_format'],
                'app.items_per_page' => $data['app_items_per_page'],
                'app.theme' => $data['app_theme'],
                'app.maintenance_mode' => (bool) ($data['app_maintenance_mode'] ?? false),
                'app.version' => $data['app_version'],
            ]);
            
            // Log audit event
            $this->appLogger->logAuditEvent(
                'application_preferences_updated',
                $this->getUser(),
                $actualChanges
            );
            
            $this->addFlash('success', 'Application preferences updated successfully.');
            return $this->redirectToRoute('admin_config_application');
        }
        
        return $this->render('admin/config/application.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/email', name: 'admin_config_email', methods: ['GET', 'POST'])]
    public function email(Request $request): Response
    {
        $emailSettings = $this->configService->getEmailSettings();
        
        $form = $this->createForm(EmailSettingsType::class, [
            'email_from_name' => $emailSettings['from_name'],
            'email_from_email' => $emailSettings['from_email'],
            'email_signature' => $emailSettings['signature'],
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $this->configService->updateMultiple([
                'email.from_name' => $data['email_from_name'],
                'email.from_email' => $data['email_from_email'],
                'email.signature' => $data['email_signature'],
            ]);
            
            $this->addFlash('success', 'Email settings updated successfully.');
            return $this->redirectToRoute('admin_config_email');
        }
        
        // Get current MAILER_DSN configuration
        $mailerDsn = $_ENV['MAILER_DSN'] ?? 'Not configured';
        $mailerFromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'Not configured';
        $mailerFromName = $_ENV['MAILER_FROM_NAME'] ?? 'Event Management System';
        
        // Parse DSN to show user-friendly info
        $emailConfig = $this->parseMailerDsn($mailerDsn);
        
        return $this->render('admin/config/email.html.twig', [
            'form' => $form->createView(),
            'mailer_config' => $emailConfig,
            'mailer_from_email' => $mailerFromEmail,
            'mailer_from_name' => $mailerFromName,
        ]);
    }

    #[Route('/email/test', name: 'admin_config_email_test', methods: ['POST'])]
    public function testEmail(Request $request): Response
    {
        $testEmail = $request->request->get('test_email');
        
        if (!$testEmail || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Please provide a valid email address.');
            return $this->redirectToRoute('admin_config_email');
        }
        
        try {
            $email = (new MimeEmail())
                ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@example.com')
                ->to($testEmail)
                ->subject('Test Email - Event Management System')
                ->html($this->getTestEmailHtml());
            
            $this->mailer->send($email);
            
            $this->addFlash('success', sprintf('Test email sent successfully to %s! Check your inbox (and spam folder).', $testEmail));
            
            // Log the test
            $this->appLogger->logAuditEvent(
                'email_configuration_tested',
                $this->getUser(),
                ['recipient' => $testEmail, 'status' => 'success']
            );
        } catch (\Exception $e) {
            $this->addFlash('error', sprintf('Failed to send test email: %s', $e->getMessage()));
            
            // Log the failure
            $this->appLogger->logAuditEvent(
                'email_configuration_test_failed',
                $this->getUser(),
                ['recipient' => $testEmail, 'error' => $e->getMessage()]
            );
        }
        
        return $this->redirectToRoute('admin_config_email');
    }

    #[Route('/email/gmail-config', name: 'admin_config_gmail', methods: ['POST'])]
    public function configureGmail(Request $request): Response
    {
        $gmailEmail = $request->request->get('gmail_email');
        $gmailPassword = $request->request->get('gmail_password');
        $fromName = $request->request->get('from_name');
        
        if (empty($gmailEmail) || !filter_var($gmailEmail, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Please provide a valid Gmail address.');
            return $this->redirectToRoute('admin_config_email');
        }
        
        if (empty($gmailPassword)) {
            $this->addFlash('error', 'Please provide your Gmail App Password.');
            return $this->redirectToRoute('admin_config_email');
        }
        
        if (empty($fromName)) {
            $fromName = 'Event Management System';
        }
        
        try {
            // Update .env file
            $envPath = $this->getParameter('kernel.project_dir') . '/.env';
            
            if (!file_exists($envPath)) {
                throw new \Exception('.env file not found');
            }
            
            // Read current .env content
            $envContent = file_get_contents($envPath);
            
            // Remove spaces from app password
            $cleanPassword = str_replace(' ', '', $gmailPassword);
            
            // Prepare new DSN
            $newDsn = sprintf('gmail+smtp://%s:%s@default', $gmailEmail, $cleanPassword);
            
            // Update or add MAILER_DSN
            if (preg_match('/^MAILER_DSN=.*$/m', $envContent)) {
                $envContent = preg_replace('/^MAILER_DSN=.*$/m', 'MAILER_DSN=' . $newDsn, $envContent);
            } else {
                $envContent .= "\nMAILER_DSN=" . $newDsn;
            }
            
            // Update or add MAILER_FROM_EMAIL
            if (preg_match('/^MAILER_FROM_EMAIL=.*$/m', $envContent)) {
                $envContent = preg_replace('/^MAILER_FROM_EMAIL=.*$/m', 'MAILER_FROM_EMAIL=' . $gmailEmail, $envContent);
            } else {
                $envContent .= "\nMAILER_FROM_EMAIL=" . $gmailEmail;
            }
            
            // Update or add MAILER_FROM_NAME
            if (preg_match('/^MAILER_FROM_NAME=.*$/m', $envContent)) {
                $envContent = preg_replace('/^MAILER_FROM_NAME=.*$/m', 'MAILER_FROM_NAME="' . $fromName . '"', $envContent);
            } else {
                $envContent .= "\nMAILER_FROM_NAME=\"" . $fromName . "\"";
            }
            
            // Backup current .env
            $backupPath = $envPath . '.backup.' . date('YmdHis');
            copy($envPath, $backupPath);
            
            // Write new content
            file_put_contents($envPath, $envContent);
            
            // Also update config service
            $this->configService->set('email.from_email', $gmailEmail);
            $this->configService->set('email.from_name', $fromName);
            
            // Log the configuration change
            $this->appLogger->logAuditEvent(
                'gmail_configuration_updated',
                $this->getUser(),
                ['email' => $gmailEmail, 'from_name' => $fromName]
            );
            
            $this->addFlash('success', 'Gmail configuration saved successfully! Please clear cache for changes to take effect.');
            $this->addFlash('info', sprintf('Backup created: %s', basename($backupPath)));
            
        } catch (\Exception $e) {
            $this->addFlash('error', sprintf('Failed to save Gmail configuration: %s', $e->getMessage()));
            
            $this->appLogger->logAuditEvent(
                'gmail_configuration_failed',
                $this->getUser(),
                ['error' => $e->getMessage()]
            );
        }
        
        return $this->redirectToRoute('admin_config_email');
    }

    private function parseMailerDsn(string $dsn): array
    {
        if ($dsn === 'Not configured') {
            return [
                'transport' => 'Not configured',
                'status' => 'warning',
                'message' => 'Email not configured. Set MAILER_DSN in .env file.'
            ];
        }
        
        // Parse DSN format: transport://user:password@host:port
        if (str_contains($dsn, 'gmail')) {
            return [
                'transport' => 'Gmail SMTP',
                'status' => 'success',
                'message' => 'Using Gmail for email delivery'
            ];
        } elseif (str_contains($dsn, 'sendgrid')) {
            return [
                'transport' => 'SendGrid',
                'status' => 'success',
                'message' => 'Using SendGrid for email delivery'
            ];
        } elseif (str_contains($dsn, 'mailgun')) {
            return [
                'transport' => 'Mailgun',
                'status' => 'success',
                'message' => 'Using Mailgun for email delivery'
            ];
        } elseif (str_contains($dsn, 'smtp')) {
            return [
                'transport' => 'SMTP',
                'status' => 'success',
                'message' => 'Using custom SMTP server'
            ];
        } elseif (str_contains($dsn, 'null')) {
            return [
                'transport' => 'Null (Dev)',
                'status' => 'info',
                'message' => 'Email disabled - using null transport (development mode)'
            ];
        }
        
        return [
            'transport' => 'Unknown',
            'status' => 'warning',
            'message' => 'Email transport configured but type is unknown'
        ];
    }

    private function getTestEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border: 1px solid #e9ecef; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { background: #e9ecef; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="header"><h1 style="margin: 0;">✅ Email Test Successful</h1></div>
    <div class="content">
        <div class="success"><strong>Success!</strong> Your email configuration is working correctly.</div>
        <p>This is a test email from your <strong>Event Management System</strong> admin panel.</p>
        <p>If you received this email, your system is ready to send:</p>
        <ul>
            <li>Event registration verification emails</li>
            <li>Password reset emails</li>
            <li>Attendee login links</li>
            <li>Admin notifications</li>
        </ul>
    </div>
    <div class="footer">
        <p><strong>Event Management System</strong></p>
        <p>This is an automated test email from the admin panel.</p>
    </div>
</body>
</html>
HTML;
    }

    #[Route('/footer', name: 'admin_config_footer', methods: ['GET', 'POST'])]
    public function footer(Request $request): Response
    {
        $footerSettings = $this->configService->getFooterSettings();
        
        $form = $this->createForm(FooterSettingsType::class, [
            'footer_text' => $footerSettings['text'],
            'footer_show_company_info' => (bool) $footerSettings['show_company_info'],
            'footer_show_version' => (bool) $footerSettings['show_version'],
            'footer_copyright_text' => $footerSettings['copyright_text'],
            'footer_link_1_text' => $footerSettings['link_1_text'],
            'footer_link_1_url' => $footerSettings['link_1_url'],
            'footer_link_2_text' => $footerSettings['link_2_text'],
            'footer_link_2_url' => $footerSettings['link_2_url'],
            'footer_link_3_text' => $footerSettings['link_3_text'],
            'footer_link_3_url' => $footerSettings['link_3_url'],
            'footer_social_facebook' => $footerSettings['social_facebook'],
            'footer_social_twitter' => $footerSettings['social_twitter'],
            'footer_social_linkedin' => $footerSettings['social_linkedin'],
            'footer_social_instagram' => $footerSettings['social_instagram'],
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $this->configService->updateMultiple([
                'footer.text' => $data['footer_text'],
                'footer.show_company_info' => (bool) ($data['footer_show_company_info'] ?? false),
                'footer.show_version' => (bool) ($data['footer_show_version'] ?? false),
                'footer.copyright_text' => $data['footer_copyright_text'],
                'footer.link_1_text' => $data['footer_link_1_text'],
                'footer.link_1_url' => $data['footer_link_1_url'],
                'footer.link_2_text' => $data['footer_link_2_text'],
                'footer.link_2_url' => $data['footer_link_2_url'],
                'footer.link_3_text' => $data['footer_link_3_text'],
                'footer.link_3_url' => $data['footer_link_3_url'],
                'footer.social_facebook' => $data['footer_social_facebook'],
                'footer.social_twitter' => $data['footer_social_twitter'],
                'footer.social_linkedin' => $data['footer_social_linkedin'],
                'footer.social_instagram' => $data['footer_social_instagram'],
            ]);
            
            $this->addFlash('success', 'Footer settings updated successfully.');
            return $this->redirectToRoute('admin_config_footer');
        }
        
        return $this->render('admin/config/footer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-defaults', name: 'admin_config_reset_defaults', methods: ['POST'])]
    public function resetDefaults(Request $request): Response
    {
        if ($this->isCsrfTokenValid('reset_defaults', $request->request->get('_token'))) {
            // This would reset all configurations to their default values
            // For safety, we'll just reinitialize defaults without overriding existing values
            $this->configService->initializeDefaults();
            $this->addFlash('info', 'Default configuration values have been restored for any missing settings.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }
        
        return $this->redirectToRoute('admin_config_index');
    }

    #[Route('/export', name: 'admin_config_export', methods: ['GET'])]
    public function export(): Response
    {
        $configurations = $this->configService->export();
        
        $response = new Response(json_encode($configurations, JSON_PRETTY_PRINT));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="app_configuration_' . date('Y-m-d_H-i-s') . '.json"');
        
        return $response;
    }

    #[Route('/cache/clear', name: 'admin_config_clear_cache', methods: ['POST'])]
    public function clearCache(Request $request): Response
    {
        if ($this->isCsrfTokenValid('clear_cache', $request->request->get('_token'))) {
            $this->configService->clearAllCache();
            $this->addFlash('success', 'Configuration cache cleared successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }
        
        return $this->redirectToRoute('admin_config_index');
    }
}