# Sakshsky Auth

A Laravel package for secure email-based authentication with device fingerprinting.

## Installation

1. Install the package via Composer:
```bash
composer require sakshsky/auth
```

2. Publish the configuration and migrations:
```bash
php artisan vendor:publish --tag=config
php artisan vendor:publish --tag=migrations
php artisan migrate
```

3. (Optional) Publish the sample routes:
```bash
php artisan vendor:publish --tag=routes
```
This creates `routes/sakshsky-auth.php`. Include it in your `RouteServiceProvider` or define your own routes.

## Configuration

Add the following to your `.env` file:
```
SAKSH_AUTH_SERVER_EMAIL=your-email@example.com
SAKSH_AUTH_PROTOCOL=imap
SAKSH_AUTH_HOST=imap.gmail.com
SAKSH_AUTH_PORT=993
SAKSH_AUTH_USER=your-email@example.com
SAKSH_AUTH_PASS=your-password
SAKSH_AUTH_POLL_INTERVAL=30000
```

Configure Laravel's mail settings in `.env` (e.g., SMTP):
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Usage

### 1. Initialize Login

You can use the provided sample route or define your own.

#### Using the Sample Route
1. Publish the sample route:
```bash
php artisan vendor:publish --tag=routes
```
2. Include the route in `app/Providers/RouteServiceProvider.php`:
```php
public function boot()
{
    $this->routes(function () {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/sakshsky-auth.php'));
    });
}
```
3. Send a POST request:
```bash
curl -X POST http://your-app/api/sakshsky-auth/init -H "Content-Type: application/json" -d '{"email":"user@example.com","socket_id":"123"}'
```

#### Defining Your Own Route
Create a route in `routes/api.php` or another route file:
```php
use Illuminate\Support\Facades\Route;
use Sakshsky\Auth\Services\FingerprintService;
use Sakshsky\Auth\Models\Verification;
use Illuminate\Support\Facades\Mail;

Route::post('/custom-auth/init', function (\Illuminate\Http\Request $request) {
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

    Mail::raw($body, function ($message) use ($toEmail, $subject) {
        $message->to($toEmail)->subject($subject);
    });

    return response()->json(['toEmail' => $toEmail, 'subject' => $subject, 'body' => $body]);
});
```
Send a POST request to your custom endpoint:
```bash
curl -X POST http://your-app/api/custom-auth/init -H "Content-Type: application/json" -d '{"email":"user@example.com","socket_id":"123"}'
```

### 2. Start Email Monitor
Run the monitor command to process incoming verification emails:
```bash
php artisan sakshsky:monitor
```

## Customization

- **Socket Notifications**: In `StartAuthMonitor.php`, add logic to emit events to your socket server:
```php
$service->start(function ($verification) {
    $this->info("Verified: {$verification->email}");
    broadcast(new \App\Events\UserVerified($verification->socket_id, $verification->email));
});
```
- **Email Templates**: Customize the `$body` variable in your route definition.

## Requirements

- PHP >= 8.1
- Laravel >= 10.0
- Composer dependencies: `ramsey/uuid`, `php-imap/php-imap`, `ua-parser/uap-php`

## License

MIT