<?php

namespace App\Service;

use App\Entity\ContactMessage;
use App\Entity\CustomerOrder;
use App\Entity\EmailLog;
use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly string $adminNotificationEmail,
        private readonly string $mailerFrom,
        private readonly string $mailerDsn,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyNewContact(ContactMessage $message): void
    {
        $from = trim($this->mailerFrom) !== '' ? trim($this->mailerFrom) : 'noreply@localhost';

        // 1. Accusé de réception au visiteur (ne dépend pas de ADMIN_NOTIFICATION_EMAIL).
        $subjectConfirm = 'CleanAvis — Nous avons bien reçu votre message';
        $previewConfirm = sprintf(
            'Confirmation pour %s <%s> — sujet : %s',
            trim($message->getFirstName().' '.$message->getLastName()),
            $message->getEmail(),
            $message->getSubject()
        );
        $confirm = (new TemplatedEmail())
            ->from($from)
            ->to($message->getEmail())
            ->subject($subjectConfirm)
            ->htmlTemplate('emails/contact_customer.html.twig')
            ->textTemplate('emails/contact_customer.txt.twig')
            ->context(['message' => $message]);

        $this->sendAndLog($confirm, 'contact_customer', $message->getEmail(), $subjectConfirm, $previewConfirm, null, $message);

        // 2. Copie équipe (nécessite ADMIN_NOTIFICATION_EMAIL).
        $subject = '[CleanAvis Contact] '.$message->getSubject();

        $previewText = sprintf(
            "Nouveau message depuis le formulaire contact.\n\nNom : %s %s\nEmail : %s\n\n%s",
            $message->getFirstName(),
            $message->getLastName(),
            $message->getEmail(),
            $message->getMessage()
        );

        $email = (new TemplatedEmail())
            ->from($from)
            ->to($this->resolveAdminRecipient())
            ->replyTo($message->getEmail())
            ->subject($subject)
            ->htmlTemplate('emails/contact_admin.html.twig')
            ->textTemplate('emails/contact_admin.txt.twig')
            ->context(['message' => $message]);

        $this->sendAndLog($email, 'contact_admin', $this->resolveAdminRecipient(), $subject, $previewText, null, $message);
    }

    public function sendPurchaseConfirmation(CustomerOrder $order): void
    {
        $from = trim($this->mailerFrom) !== '' ? trim($this->mailerFrom) : 'noreply@localhost';
        $subjectCustomer = 'CleanAvis — Paiement confirmé ('.$order->getReference().')';

        $customerPreview = sprintf(
            'Confirmation paiement %s — %s',
            $order->getReference(),
            $order->getEmail()
        );

        $customerEmail = (new TemplatedEmail())
            ->from($from)
            ->to($order->getEmail())
            ->subject($subjectCustomer)
            ->htmlTemplate('emails/purchase_customer.html.twig')
            ->textTemplate('emails/purchase_customer.txt.twig')
            ->context(['order' => $order]);

        $this->sendAndLog($customerEmail, 'purchase_customer', $order->getEmail(), $subjectCustomer, $customerPreview, $order, null);

        $subjectAdmin = '[CleanAvis] Nouveau paiement '.$order->getReference();
        $adminPreview = sprintf(
            'Paiement %s — client %s <%s>',
            $order->getReference(),
            trim($order->getFirstName().' '.$order->getLastName()),
            $order->getEmail()
        );

        $adminMail = (new TemplatedEmail())
            ->from($from)
            ->to($this->resolveAdminRecipient())
            ->subject($subjectAdmin)
            ->htmlTemplate('emails/purchase_admin.html.twig')
            ->textTemplate('emails/purchase_admin.txt.twig')
            ->context(['order' => $order]);

        $this->sendAndLog($adminMail, 'purchase_admin', $this->resolveAdminRecipient(), $subjectAdmin, $adminPreview, $order, null);
    }

    private function resolveAdminRecipient(): string
    {
        $fromDb = trim($this->siteSettingsRepository->getSingleton()->getAdminNotificationEmail());
        if ($fromDb !== '') {
            return $fromDb;
        }

        $fromEnv = trim($this->adminNotificationEmail);

        return $fromEnv !== '' ? $fromEnv : 'admin@localhost';
    }

    private function sendAndLog(
        Email $email,
        string $type,
        string $toDisplay,
        string $subject,
        string $bodyPreview,
        ?CustomerOrder $order,
        ?ContactMessage $contact,
    ): void {
        $log = new EmailLog();
        $log->setEmailType($type);
        $log->setToEmail($toDisplay);
        $log->setSubject($subject);
        $log->setBodyPreview(mb_substr($bodyPreview, 0, 500));
        $log->setCustomerOrder($order);
        $log->setContactMessage($contact);

        $preflight = $this->preflightMailError($type);
        if ($preflight !== null) {
            $log->setSuccess(false);
            $log->setErrorMessage($preflight);
            $this->logger->warning('E-mail non envoyé (prévol)', ['type' => $type, 'reason' => $preflight]);
            $this->entityManager->persist($log);
            $this->entityManager->flush();

            return;
        }

        try {
            $this->mailer->send($email);
            $log->setSuccess(true);
        } catch (\Throwable $e) {
            $log->setSuccess(false);
            $log->setErrorMessage($e->getMessage());
            $this->logger->error('Échec envoi e-mail', [
                'type' => $type,
                'to' => $toDisplay,
                'exception' => $e->getMessage(),
            ]);
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * @return non-empty-string|null erreur bloquante, ou null si on peut tenter l’envoi
     */
    private function preflightMailError(string $type): ?string
    {
        $dsn = trim($this->mailerDsn);
        if ($dsn === '' || str_starts_with($dsn, 'null://')) {
            return 'Mailer désactivé : MAILER_DSN est null ou vide. Définissez brevo+api://… ou brevo+smtp://… dans .env (voir .env.example).';
        }

        if (trim($this->mailerFrom) === '') {
            return 'MAILER_FROM est vide : définissez un expéditeur déjà vérifié dans Brevo (Senders).';
        }

        $needsAdminInbox = \in_array($type, ['contact_admin', 'purchase_admin'], true);
        if ($needsAdminInbox && !$this->hasConfiguredAdminNotificationRecipient()) {
            return 'Indiquez un e-mail de réception dans Administration → Paramètres du site, ou définissez ADMIN_NOTIFICATION_EMAIL dans .env.';
        }

        return null;
    }

    private function hasConfiguredAdminNotificationRecipient(): bool
    {
        $fromDb = trim($this->siteSettingsRepository->getSingleton()->getAdminNotificationEmail());

        return $fromDb !== '' || trim($this->adminNotificationEmail) !== '';
    }
}
