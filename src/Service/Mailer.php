<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class Mailer
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send code through.
     *
     * @param User $user    User object
     * @param int $code     Authentication code
     *
     * @return Email
     * @throws TransportExceptionInterface
     */
    public function sendCode(User $user, int $code)
    {
        $email = (new Email())
            ->from('acme@company.com')
            ->to($user->getEmail())
            ->subject('Authentication requested code')
            ->text('Your code is: '.$code);

        $this->mailer->send($email);

        return $email;
    }
}