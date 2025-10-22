<?php

namespace App\Controller\Admin;

use App\Service\ApplicationLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminLogController extends AbstractController
{
    public function __construct(
        private ApplicationLogger $appLogger,
        private string $projectDir
    ) {
    }

    #[Route('/', name: 'admin_logs_index', methods: ['GET'])]
    public function index(): Response
    {
        $logDir = $this->projectDir . '/var/log';
        $logFiles = $this->getLogFiles($logDir);

        return $this->render('admin/logs/index.html.twig', [
            'log_files' => $logFiles,
            'log_dir' => $logDir,
        ]);
    }

    #[Route('/view/{logFile}', name: 'admin_logs_view', methods: ['GET'])]
    public function view(string $logFile, Request $request): Response
    {
        $logDir = $this->projectDir . '/var/log';
        $filePath = $logDir . '/' . $logFile;

        if (!file_exists($filePath) || !$this->isAllowedLogFile($logFile)) {
            throw $this->createNotFoundException('Log file not found or not accessible.');
        }

        $lines = (int) ($request->query->get('lines', 500));
        $search = $request->query->get('search', '');
        $level = $request->query->get('level', '');
        $startDate = $request->query->get('start_date', '');
        $endDate = $request->query->get('end_date', '');

        $logContent = $this->readLogFile($filePath, $lines, $search, $level, $startDate, $endDate);

        return $this->render('admin/logs/view.html.twig', [
            'log_file' => $logFile,
            'log_content' => $logContent,
            'filters' => [
                'lines' => $lines,
                'search' => $search,
                'level' => $level,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    #[Route('/download/{logFile}', name: 'admin_logs_download', methods: ['GET'])]
    public function download(string $logFile): Response
    {
        $logDir = $this->projectDir . '/var/log';
        $filePath = $logDir . '/' . $logFile;

        if (!file_exists($filePath) || !$this->isAllowedLogFile($logFile)) {
            throw $this->createNotFoundException('Log file not found or not accessible.');
        }

        // Log the download action for audit trail
        $this->appLogger->logAuditEvent(
            'log_file_downloaded',
            $this->getUser(),
            ['log_file' => $logFile]
        );

        return $this->file($filePath, $logFile);
    }

    #[Route('/tail/{logFile}', name: 'admin_logs_tail', methods: ['GET'])]
    public function tail(string $logFile): JsonResponse
    {
        $logDir = $this->projectDir . '/var/log';
        $filePath = $logDir . '/' . $logFile;

        if (!file_exists($filePath) || !$this->isAllowedLogFile($logFile)) {
            return new JsonResponse(['error' => 'Log file not found or not accessible.'], 404);
        }

        // Get last 50 lines
        $lines = $this->tailFile($filePath, 50);

        return new JsonResponse([
            'lines' => $lines,
            'timestamp' => time(),
        ]);
    }

    #[Route('/clear/{logFile}', name: 'admin_logs_clear', methods: ['POST'])]
    public function clear(string $logFile, Request $request): Response
    {
        $logDir = $this->projectDir . '/var/log';
        $filePath = $logDir . '/' . $logFile;

        if (!file_exists($filePath) || !$this->isAllowedLogFile($logFile)) {
            throw $this->createNotFoundException('Log file not found or not accessible.');
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('clear_log_' . $logFile, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_logs_index');
        }

        // Backup the current log before clearing (just in case)
        $backupPath = $filePath . '.backup.' . date('Y-m-d-H-i-s');
        copy($filePath, $backupPath);

        // Clear the log file
        file_put_contents($filePath, '');

        // Log the clear action for audit trail
        $this->appLogger->logAuditEvent(
            'log_file_cleared',
            $this->getUser(),
            [
                'log_file' => $logFile,
                'backup_created' => $backupPath,
            ]
        );

        $this->addFlash('success', "Log file '{$logFile}' has been cleared. A backup was created.");
        return $this->redirectToRoute('admin_logs_view', ['logFile' => $logFile]);
    }

    private function getLogFiles(string $logDir): array
    {
        if (!is_dir($logDir)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()
            ->in($logDir)
            ->name('*.log')
            ->sortByName();

        $files = [];
        foreach ($finder as $file) {
            $files[] = [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'readable' => $file->isReadable(),
            ];
        }

        return $files;
    }

    private function isAllowedLogFile(string $filename): bool
    {
        // Only allow .log files and prevent directory traversal
        return preg_match('/^[\w\-\.]+\.log$/', $filename) && !str_contains($filename, '..');
    }

    private function readLogFile(string $filePath, int $lines, string $search, string $level, string $startDate, string $endDate): array
    {
        $logEntries = [];
        
        // Read the file in reverse to get the most recent entries first
        $fileLines = $this->tailFile($filePath, $lines * 2); // Get more lines to account for filtering
        
        foreach ($fileLines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $entry = $this->parseLogLine($line);
            
            // Apply filters
            if (!empty($search) && stripos($line, $search) === false) {
                continue;
            }
            
            if (!empty($level) && $entry['level'] !== strtoupper($level)) {
                continue;
            }
            
            if (!empty($startDate) && $entry['datetime'] && $entry['datetime'] < new \DateTime($startDate)) {
                continue;
            }
            
            if (!empty($endDate) && $entry['datetime'] && $entry['datetime'] > new \DateTime($endDate . ' 23:59:59')) {
                continue;
            }

            $logEntries[] = $entry;
            
            if (count($logEntries) >= $lines) {
                break;
            }
        }

        return $logEntries;
    }

    private function parseLogLine(string $line): array
    {
        // Try to parse Monolog format: [datetime] channel.LEVEL: message context extra
        $pattern = '/^\[([^\]]+)\] ([^\.]+)\.(\w+): (.+)$/';
        
        if (preg_match($pattern, $line, $matches)) {
            try {
                $datetime = new \DateTime($matches[1]);
            } catch (\Exception $e) {
                $datetime = null;
            }
            
            return [
                'raw' => $line,
                'datetime' => $datetime,
                'channel' => $matches[2],
                'level' => $matches[3],
                'message' => $matches[4],
                'formatted_datetime' => $datetime ? $datetime->format('Y-m-d H:i:s') : $matches[1],
            ];
        }

        // Fallback for non-standard format
        return [
            'raw' => $line,
            'datetime' => null,
            'channel' => 'unknown',
            'level' => 'INFO',
            'message' => $line,
            'formatted_datetime' => 'Unknown',
        ];
    }

    private function tailFile(string $filePath, int $lines): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        // Use system tail command if available for better performance
        if (function_exists('exec') && !empty(shell_exec('which tail'))) {
            $output = [];
            exec("tail -n {$lines} " . escapeshellarg($filePath), $output);
            return array_reverse($output);
        }

        // PHP fallback
        $file = new \SplFileObject($filePath);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $result = [];

        $file->rewind();
        $file->seek($startLine);

        while (!$file->eof()) {
            $line = trim($file->fgets());
            if (!empty($line)) {
                $result[] = $line;
            }
        }

        return array_reverse($result);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}