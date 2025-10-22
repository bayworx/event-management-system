<?php

namespace App\Twig;

use App\Entity\Administrator;
use App\Repository\MessageRepository;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MessageExtension extends AbstractExtension
{
    public function __construct(
        private MessageRepository $messageRepository,
        private Security $security
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_unread_message_count', [$this, 'getAdminUnreadMessageCount']),
        ];
    }

    public function getAdminUnreadMessageCount(): int
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof Administrator) {
            return 0;
        }

        return $this->messageRepository->countUnreadForAdmin($user);
    }
}