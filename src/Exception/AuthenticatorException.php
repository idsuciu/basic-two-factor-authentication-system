<?php


namespace App\Exception;


use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;


class AuthenticatorException extends AuthenticationException
{
    /**
     * @var UserRepository
     */
    private $user;

    /**
     * @var string Custom error message
     */
    protected $message;

    protected $messageData = [];

    public function __construct(UserInterface $user = null, $message = '', $messageData = [], $code = 400)
    {
        parent::__construct($message, $code);

        $this->user = $user;
        $this->setMessage($message);
    }

    /**
     * Initializes this exception class
     */
    public function readyForStepTwo()
    {
        throw new AuthenticatorException($this->user);
    }

    /**
     * Get User.
     *
     * @return UserRepository|UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set a message that will be shown to the user.
     *
     * @param string $message    Data to be passed to the thrown exception
     * @param array $messageData Data to be passed into the translator
     */
    public function setMessage(string $message,  $messageData = [])
    {
        $this->message = $message;
        $this->messageData = $messageData;
    }

    /**
     * Get message key.
     *
     * @return string
     */
    public function getMessageKey()
    {
        return $this->message;
    }

    /**
     * Get message data.
     *
     * @return array
     */
    public function getMessageData()
    {
        return $this->messageData;
    }

}
