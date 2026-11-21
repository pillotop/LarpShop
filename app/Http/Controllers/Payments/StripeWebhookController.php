<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeWebhookHandler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeWebhookHandler $handler): Response
    {
        try {
            $handler->handle($request->getContent(), $request->header('Stripe-Signature', ''));
        } catch (ConnectionException|RequestException) {
            // Keep provider responses private and let Stripe retry delivery later.
            return response('', 503);
        }

        return response()->noContent();
    }
}
