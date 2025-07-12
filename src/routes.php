<?php

use Illuminate\Support\Facades\Route;
use Sakshsky\Auth\Services\FingerprintService;
use Sakshsky\Auth\Models\Verification;
use Illuminate\Support\Facades\Mail;

/*
 * Sample route for Sakshsky Auth login initialization.
 * Publish this file using `php artisan vendor:publish --tag=routes`
 * and include it in your route service provider, or define your own route.
 */
Route::post('/sakshsky-auth/init', function (\Illuminate\Http\Request $request) {
    $email = $request->input('email');
    $socketId = $request->input('socket_id');

    if (!$email || !$socketId) {
        return response()->json(['error' => 'Missing email or socketId'], 400);
    }

    $fingerprintService = new FingerprintService();
    $code = $fingerprintService->generateCode();
    $expiry = now()->addMinutes(10);
    $fingerprintString = $fingerprintService->collectFingerprint($request);
    $salt = $fingerprintService->generateSalt();
    $hash = $fingerprintService->generateFingerprintHash($fingerprintString, $code, $salt);
    $hashedFingerprint = hash('sha256', $fingerprintString);

    $verification = Verification::create([
        'email' => $email,
        'code' => $code,
        'expiry' => $expiry,
        'socket_id' => $socketId,
        'hashed_fingerprint' => $hashedFingerprint,
        'salt' => $salt,
    ]);

    $toEmail = config('sakshsky-auth.server_email');
    $subject = 'Login Verification';
    $body = "Verification Code: $code\nHash: $hash\n\nPlease do not edit this email.";

    // Send email
    Mail::raw($body, function ($message) use ($toEmail, $subject) {
        $message->to($toEmail)->subject($subject);
    });

    return response()->json(['toEmail' => $toEmail, 'subject' => $subject, 'body' => $body]);
});