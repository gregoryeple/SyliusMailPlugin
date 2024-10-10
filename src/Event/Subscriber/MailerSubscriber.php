<?php

declare(strict_types = 1);

namespace BeHappy\SyliusMailPlugin\Event\Subscriber;

use BeHappy\SyliusMailPlugin\Entity\MailConfiguration;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Mailer\Event\EmailSendEvent;
use Sylius\Component\Mailer\SyliusMailerEvents;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Class MailerSubscriber
 *
 * @package BeHappy\MailPlugin\Event\Subscriber
 */
class MailerSubscriber implements EventSubscriberInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    /**
     * MailerSubscriber constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }
    
    /**
     * @return array
     */
    public static function getSubScribedEvents(): array
    {
        return [
            SyliusMailerEvents::EMAIL_PRE_SEND => 'preSend',
        ];
    }
    
    /**
     * @param EmailSendEvent $event
     *
     * @throws InvalidArgumentException
     */
    public function preSend(EmailSendEvent $event): void
    {
        /** @var Channel $channel */
        $channel = $this->container->get('sylius.context.channel')->getChannel();
        $em = $this->container->get('doctrine')->getManager();
        /** @var MailConfiguration $configuration */
        $configuration = $em->getRepository(MailConfiguration::class)->findOneByChannel($channel);
        /** @var Email $message */
        $message = $event->getMessage();
        
        $message->from(new Address($configuration->getSenderMail(), $configuration->getSenderName()));
        
        if (!empty($configuration->getReplyToMail())) {
            $emailValidator = new EmailValidator();
            if ($emailValidator->isValid($configuration->getReplyToMail(), new RFCValidation())) {
                $message->addReplyTo($configuration->getReplyToMail());
            }
        }
        
        if ($configuration->isDkim()) {
            if (!$configuration->isDkimReady()) {
                throw new \LogicException('Missing fields for DKIM sending');
            }
            $signer = $this->getDkimSigner($configuration->getDkimKey(), $configuration->getDkimDomain(), $configuration->getDkimSelector());
            $signer->sign($message);
        }
    }
    
    /**
     * @param string $dkimKey
     * @param string $domainName
     * @param string $selector
     *
     * @return DkimSigner
     * @throws InvalidArgumentException
     */
    protected function getDkimSigner(string $dkimKey, string $domainName, string $selector): DkimSigner
    {
        $signer = new DkimSigner($dkimKey, $domainName, $selector);
        
        return $signer;
    }
}