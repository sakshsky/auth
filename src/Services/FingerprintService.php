<?php

namespace Sakshsky\Auth\Services;

use Ramsey\Uuid\Uuid;
use uaparser\Parser;

class FingerprintService
{
    public function collectFingerprint($request)
    {
        $ip = $request->ip();
        $userAgent = $request->header('User-Agent', '');
        $parser = Parser::create();
        $uaResult = $parser->parse($userAgent);

        $browser = ($uaResult->ua->family . ' ' . $uaResult->ua->major) ?: 'Unknown';
        $device = ($uaResult->device->model . ' (' . $uaResult->device->family . ')') ?: 'Unknown';
        $os = ($uaResult->os->family . ' ' . $uaResult->os->major) ?: 'Unknown';
        $referrer = $request->header('referer', 'None');
        $acceptLanguage = $request->header('accept-language', 'None');
        $connectionType = $request->header('connection', 'Unknown');
        $dnt = $request->header('dnt') ? 'Do Not Track Enabled' : 'Do Not Track Disabled';

        return implode('|', [$ip, $userAgent, $browser, $device, $os, $referrer, $acceptLanguage, $connectionType, $dnt]);
    }

    public function generateFingerprintHash($fingerprintString, $code, $salt)
    {
        $dataToHash = $code . $fingerprintString . $salt;
        return hash('sha256', $dataToHash);
    }

    public function generateCode()
    {
        return Uuid::uuid4()->toString();
    }

    public function generateSalt()
    {
        return bin2hex(random_bytes(16));
    }
}