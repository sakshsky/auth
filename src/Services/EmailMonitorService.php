<?php

namespace Sakshsky\Auth\Services;

use PhpImap\Mailbox;
use Sakshsky\Auth\Models\Verification;

class EmailMonitorService
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function start($onVerified)
    {
        $mailbox = new Mailbox(
            "{{$this->config['host']}:{$this->config['port']}/{$this->config['protocol']}/ssl}",
            $this->config['user'],
            $this->config['pass'],
            'INBOX'
        );

        while (true) {
            $mailsIds = $mailbox->searchMailbox('UNSEEN');
            foreach ($mailsIds as $mailId) {
                $mail = $mailbox->getMail($mailId);
                $bodyPlain = $mail->textPlain;

                preg_match('/Verification Code: (.+)/', $bodyPlain, $codeMatch);
                preg_match('/Hash: (.+)/', $bodyPlain, $hashMatch);

                if (!$codeMatch || !$hashMatch) continue;

                $code = trim($codeMatch[1]);
                $extractedHash = trim($hashMatch[1]);

                $verification = Verification::where('code', $code)->first();
                if (!$verification || now()->gt($verification->expiry) || $mail->fromAddress !== $verification->email) continue;

                $fingerprintService = new FingerprintService();
                $reHash = $fingerprintService->generateFingerprintHash(
                    $verification->hashed_fingerprint,
                    $code,
                    $verification->salt
                );

                if ($reHash !== $extractedHash) continue;

                $onVerified($verification);

                Verification::where('code', $code)->delete();
                $mailbox->markMailAsRead($mailId);
                $mailbox->deleteMail($mailId);
            }
            sleep($this->config['poll_interval'] / 1000);
        }
    }
}