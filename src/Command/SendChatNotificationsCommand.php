<?php

namespace App\Command;

use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:send-chat-notifications',
    description: 'Send email notifications for unread chat messages',
)]
class SendChatNotificationsCommand extends Command
{
    public function __construct(
        private ChatMessageRepository $chatMessageRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pendingMessages = $this->chatMessageRepository->findPendingNotifications();

        if (empty($pendingMessages)) {
            $io->info('No pending notifications.');
            return Command::SUCCESS;
        }

        foreach ($pendingMessages as $message) {
            $recipient = $message->getDestinataire();
            $sender = $message->getAuteur();

            $email = (new Email())
                ->from('no-reply@sportinsight.com')
                ->to($recipient->getEmail())
                ->subject(sprintf('Vous avez un nouveau message de %s !', $sender->getNom()))
                ->html($this->renderEmailHtml($message));

            try {
                $this->mailer->send($email);
                $message->setNotificationSent(true);
                $this->entityManager->persist($message);
                $io->success(sprintf('Notification sent to %s', $recipient->getEmail()));
            } catch (\Exception $e) {
                $io->error(sprintf('Failed to send email to %s: %s', $recipient->getEmail(), $e->getMessage()));
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function renderEmailHtml($message): string
    {
        $senderName = $message->getAuteur()->getNom();
        $annonceTitle = $message->getAnnonce()->getTitre();
        $chatUrl = $this->urlGenerator->generate('front_annonce_chat', ['id' => $message->getAnnonce()->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return sprintf(
            '<h2>Bonjour !</h2>
            <p>Vous avez reçu un nouveau message de <strong>%s</strong> concernant l\'annonce "<strong>%s</strong>".</p>
            <p><em>"%s"</em></p>
            <p><a href="%s">Cliquez ici pour répondre</a></p>',
            htmlspecialchars($senderName),
            htmlspecialchars($annonceTitle),
            htmlspecialchars(substr($message->getMessage(), 0, 100)) . (strlen($message->getMessage()) > 100 ? '...' : ''),
            $chatUrl
        );
    }
}
