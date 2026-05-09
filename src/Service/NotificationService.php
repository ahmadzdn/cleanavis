<?php

namespace App\Service;

use App\Entity\ContactMessage;
use App\Entity\CustomerOrder;
use App\Entity\EmailLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $adminNotificationEmail,
        private readonly string $mailerFrom,
    ) {
    }

    public function notifyNewContact(ContactMessage $message): void
    {
        $from = trim($this->mailerFrom) !== '' ? trim($this->mailerFrom) : 'noreply@localhost';
        $subject = '[CleanAvis Contact] '.$message->getSubject();

        $body = sprintf(
            "Nouveau message depuis le formulaire contact.\n\nNom : %s %s\nEmail : %s\n\n%s",
            $message->getFirstName(),
            $message->getLastName(),
            $message->getEmail(),
            $message->getMessage()
        );

        $email = (new Email())
            ->from($from)
            ->to($this->resolveAdminRecipient())
            ->replyTo($message->getEmail())
            ->subject($subject)
            ->text($body);

        $this->sendAndLog($email, 'contact_admin', $this->resolveAdminRecipient(), $subject, $body, null, $message);
    }

    public function sendPurchaseConfirmation(CustomerOrder $order): void
    {
        $from = trim($this->mailerFrom) !== '' ? trim($this->mailerFrom) : 'noreply@localhost';
        $subject = 'CleanAvis — Paiement confirmé ('.$order->getReference().')';

        $amount = $order->getAmountTotal() !== null
            ? number_format($order->getAmountTotal() / 100, 2, ',', ' ').' €'
            : '—';

        $body = <<<TXT
Bonjour {$order->getFirstName()},

Merci pour votre confiance. Votre paiement est bien enregistré.

Référence dossier : {$order->getReference()}
Montant : {$amount}

Notre équipe prend en charge votre dossier et vous tient informé(e) des étapes suivantes.

Cordialement,
L'équipe CleanAvis
TXT;

        $customerEmail = (new Email())
            ->from($from)
            ->to($order->getEmail())
            ->subject($subject)
            ->text($body);

        $this->sendAndLog($customerEmail, 'purchase_customer', $order->getEmail(), $subject, $body, $order, null);

        $adminBody = $body."\n\n---\nEmail client : ".$order->getEmail()."\nURL avis : ".$order->getReviewUrl()."\nJustification :\n".$order->getJustification();

        $adminMail = (new Email())
            ->from($from)
            ->to($this->resolveAdminRecipient())
            ->subject('[CleanAvis] Nouveau paiement '.$order->getReference())
            ->text($adminBody);

        $this->sendAndLog($adminMail, 'purchase_admin', $this->resolveAdminRecipient(), $adminMail->getSubject(), $adminBody, $order, null);
    }

    private function resolveAdminRecipient(): string
    {
        $e = trim($this->adminNotificationEmail);

        return $e !== '' ? $e : 'admin@localhost';
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

        try {
            $this->mailer->send($email);
            $log->setSuccess(true);
        } catch (\Throwable $e) {
            $log->setSuccess(false);
            $log->setErrorMessage($e->getMessage());
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
