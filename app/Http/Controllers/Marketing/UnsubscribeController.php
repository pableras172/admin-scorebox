<?php

declare(strict_types=1);

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\EmailUnsubscribe;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class UnsubscribeController extends Controller
{
    /**
     * Handle 1-click unsubscribe via email link (GET).
     */
    public function unsubscribe(Request $request): View
    {
        $email = strtolower(trim((string) $request->query('email', '')));

        if ($email !== '') {
            EmailUnsubscribe::unsubscribe(
                email: $email,
                source: EmailUnsubscribe::SOURCE_LINK,
                reason: 'user_click'
            );
        }

        $resubscribeUrl = URL::signedRoute('marketing.resubscribe', [
            'email' => $email,
        ]);

        return view('marketing.unsubscribed', [
            'email' => $email,
            'resubscribeUrl' => $resubscribeUrl,
        ]);
    }

    /**
     * Handle RFC 8058 One-Click unsubscribe via POST.
     */
    public function unsubscribePost(Request $request): Response
    {
        $email = strtolower(trim((string) ($request->input('email') ?? $request->query('email', ''))));

        if ($email !== '') {
            EmailUnsubscribe::unsubscribe(
                email: $email,
                source: EmailUnsubscribe::SOURCE_HEADER,
                reason: 'rfc8058_one_click'
            );
        }

        return response('Unsubscribed successfully', 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Handle voluntary resubscription reversal via signed POST.
     */
    public function resubscribe(Request $request): View
    {
        $email = strtolower(trim((string) ($request->input('email') ?? $request->query('email', ''))));

        if ($email !== '') {
            EmailUnsubscribe::resubscribe($email);
        }

        return view('marketing.resubscribed', [
            'email' => $email,
        ]);
    }
}
