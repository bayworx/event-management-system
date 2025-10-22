<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class ApplicationLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private LoggerInterface $securityLogger,
        private LoggerInterface $auditLogger,
        private LoggerInterface $eventMgmtLogger,
        private LoggerInterface $userMgmtLogger,
        private LoggerInterface $appErrorLogger
    ) {
    }

    /**
     * Log security-related events (login, logout, access denied, etc.)
     */
    public function logSecurityEvent(string $event, ?UserInterface $user = null, ?Request $request = null, array $context = []): void
    {
        $logData = [
            'event' => $event,
            'user_id' => $user?->getUserIdentifier(),
            'ip_address' => $request?->getClientIp(),
            'user_agent' => $request?->headers->get('User-Agent'),
            'url' => $request?->getUri(),
            'method' => $request?->getMethod(),
            'timestamp' => new \DateTime(),
            'context' => $context,
        ];

        $this->securityLogger->info($event, $logData);
    }

    /**
     * Log audit trail for admin actions and configuration changes
     */
    public function logAuditEvent(string $action, ?UserInterface $user = null, array $changes = [], array $context = []): void
    {
        $logData = [
            'action' => $action,
            'user_id' => $user?->getUserIdentifier(),
            'changes' => $changes,
            'timestamp' => new \DateTime(),
            'context' => $context,
        ];

        $this->auditLogger->info($action, $logData);
    }

    /**
     * Log event management operations
     */
    public function logEventOperation(string $operation, int $eventId = null, ?UserInterface $user = null, array $context = []): void
    {
        $logData = [
            'operation' => $operation,
            'event_id' => $eventId,
            'user_id' => $user?->getUserIdentifier(),
            'timestamp' => new \DateTime(),
            'context' => $context,
        ];

        $level = $this->getLogLevelForOperation($operation);
        $this->eventMgmtLogger->log($level, $operation, $logData);
    }

    /**
     * Log user management operations
     */
    public function logUserOperation(string $operation, int $userId = null, ?UserInterface $adminUser = null, array $context = []): void
    {
        $logData = [
            'operation' => $operation,
            'target_user_id' => $userId,
            'admin_user_id' => $adminUser?->getUserIdentifier(),
            'timestamp' => new \DateTime(),
            'context' => $context,
        ];

        $level = $this->getLogLevelForOperation($operation);
        $this->userMgmtLogger->log($level, $operation, $logData);
    }

    /**
     * Log application-specific errors
     */
    public function logApplicationError(string $error, \Throwable $exception = null, array $context = []): void
    {
        $logData = [
            'error' => $error,
            'exception_class' => $exception ? get_class($exception) : null,
            'exception_message' => $exception?->getMessage(),
            'exception_trace' => $exception?->getTraceAsString(),
            'timestamp' => new \DateTime(),
            'context' => $context,
        ];

        $this->appErrorLogger->error($error, $logData);
    }

    /**
     * Log general application events
     */
    public function logInfo(string $message, array $context = []): void
    {
        $logData = [
            'message' => $message,
            'timestamp' => new \DateTime(),
            'context' => $context,
        ];

        $this->logger->info($message, $logData);
    }

    /**
     * Log warnings
     */
    public function logWarning(string $message, array $context = []): void
    {
        $logData = [
            'message' => $message,
            'timestamp' => new \DateTime(),
            'context' => $context,
        ];

        $this->logger->warning($message, $logData);
    }

    /**
     * Log errors
     */
    public function logError(string $message, \Throwable $exception = null, array $context = []): void
    {
        $logData = [
            'message' => $message,
            'exception_class' => $exception ? get_class($exception) : null,
            'exception_message' => $exception?->getMessage(),
            'exception_file' => $exception?->getFile(),
            'exception_line' => $exception?->getLine(),
            'timestamp' => new \DateTime(),
            'context' => $context,
        ];

        $this->logger->error($message, $logData);
    }

    /**
     * Get appropriate log level for different operations
     */
    private function getLogLevelForOperation(string $operation): string
    {
        $criticalOperations = ['delete', 'remove', 'cancel', 'suspend'];
        $warningOperations = ['update', 'modify', 'change', 'edit'];
        
        foreach ($criticalOperations as $critical) {
            if (stripos($operation, $critical) !== false) {
                return 'critical';
            }
        }
        
        foreach ($warningOperations as $warning) {
            if (stripos($operation, $warning) !== false) {
                return 'warning';
            }
        }
        
        return 'info';
    }

    /**
     * Format user info for logging
     */
    public function formatUserInfo(?UserInterface $user): array
    {
        if (!$user) {
            return ['user_id' => null, 'user_type' => 'anonymous'];
        }

        return [
            'user_id' => $user->getUserIdentifier(),
            'user_roles' => $user->getRoles(),
        ];
    }

    /**
     * Format request info for logging
     */
    public function formatRequestInfo(?Request $request): array
    {
        if (!$request) {
            return [];
        }

        return [
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'referer' => $request->headers->get('Referer'),
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'route' => $request->attributes->get('_route'),
        ];
    }
}