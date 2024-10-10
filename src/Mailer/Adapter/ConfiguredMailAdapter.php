<?php

declare(strict_types = 1);

namespace BeHappy\SyliusMailPlugin\Mailer\Adapter;

use BeHappy\SyliusMailPlugin\Entity\MailConfiguration;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Mailer\Event\EmailSendEvent;
use Sylius\Component\Mailer\Model\EmailInterface;
use Sylius\Component\Mailer\Renderer\RenderedEmail;
use Sylius\Component\Mailer\Sender\Adapter\AbstractAdapter;
use Sylius\Component\Mailer\SyliusMailerEvents;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class ConfiguredMailAdapter extends AbstractAdapter implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    /**
     * @var Mailer
     */
    protected $mailer;
    
    /**
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }
    
    /**
     * @param array          $recipients
     * @param string         $senderAddress
     * @param string         $senderName
     * @param RenderedEmail  $renderedEmail
     * @param EmailInterface $email
     * @param array          $data
     * @param array          $attachments
     * @param array          $replyTo
     */
    public function send(array $recipients, string $senderAddress, string $senderName, RenderedEmail $renderedEmail,
                         EmailInterface $email, array $data, array $attachments = [], array $replyTo = []): void
    {
        /** @var Channel $channel */
        $channel = $this->container->get('sylius.context.channel')->getChannel();
        $em = $this->container->get('doctrine')->getManager();
        /** @var MailConfiguration $configuration */
        $configuration = $em->getRepository(MailConfiguration::class)->findOneByChannel($channel);
        
        $message = (new Email())
            ->subject($renderedEmail->getSubject())
            ->from(new Address($senderAddress, $senderName))
            ->to(...$recipients)
            ->replyTo(...$replyTo);
        
        $message->html($renderedEmail->getBody());
        
        foreach ($attachments as $attachment) {
            $file = new File($attachment);
            
            $message->addPart(new DataPart($file, $file->getFilename()));
        }
        
        $emailSendEvent = new EmailSendEvent($message, $email, $data, $recipients, $replyTo);
        
        $this->dispatcher->dispatch(SyliusMailerEvents::EMAIL_PRE_SEND, $emailSendEvent);
        
        //Select transport mode depending on configuration
        $sendingType = $configuration->getType();
        switch ($sendingType) {
            case MailConfiguration::TYPE_SMTP:
                $dnsOptions = [
                    "encryption" => ($configuration->getEncryption() === MailConfiguration::ENCRYPTION_TLS ? 'tls' : 'ssl')
                ];
                $this->mailer = new Mailer((new EsmtpTransportFactory())->create(new Dsn(
                    "smtp",
                    $configuration->getSmtpHost(),
                    $configuration->getSmtpUser(),
                    $configuration->getSmtpPassword(),
                    $configuration->getSmtpPort(),
                    $dnsOptions
                )));
                break;
            case MailConfiguration::TYPE_DIRECT:
            default:
                break;
        }
        
        $this->mailer->send($message);
        $this->dispatcher->dispatch(SyliusMailerEvents::EMAIL_POST_SEND, $emailSendEvent);
    }
}