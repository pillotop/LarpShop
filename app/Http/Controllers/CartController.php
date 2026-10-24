<?php

namespace App\Http\Controllers;

use App\Services\Cart\CartMutationService;
use App\Services\Cart\CartRetrievalService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private CartRetrievalService $cartRetrievalService,
        private CartMutationService $cartMutationService,
    ) {}

    public function index(): View
    {
        return view('cart.pages.index');
    }

    public function show(): JsonResponse
    {
        $products = $this
            ->cartRetrievalService
            ->getCartData();

        return response()->json([
            'success' => true,
            'cart' => $products,
            'cart_html' => view('components.cart', ['products' => $products['products']])->render(),
            'cart_page_html' => view('cart.partials.content', ['cart' => $products])->render(),
        ]);
    }

    public function add(
        Request $request,
    ): JsonResponse {
        return $this->writeQuantity($request, add: true);
    }

    public function store(
        Request $request,
    ): JsonResponse {
        return $this->writeQuantity($request, add: false);
    }

    private function writeQuantity(
        Request $request,
        bool $add,
    ): JsonResponse {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'product_id')],
            'quantity' => ['required', 'integer', $add ? 'min:1' : 'min:0', 'max:2147483647'],
        ]);

        try {
            $result = $this->cartMutationService->updateQuantity(
                (int) $validated['product_id'],
                (int) $validated['quantity'],
                $add
            );
        } catch (LockTimeoutException) {
            return response()->json([
                'success' => false,
                'error' => 'Your cart is busy. Please try again.',
            ], 409);
        }

        $cart = $this->cartRetrievalService->getCartData();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated.',
            'cart' => $cart,
            'cart_html' => view('components.cart', ['products' => $cart['products']])->render(),
            'cart_page_html' => view('cart.partials.content', ['cart' => $cart])->render(),
            // Preserve the existing total-count field for current callers.
            'quantity' => $result['total_count'],
            ...$result,
        ]);
    }

    public function sort(?string $sortBy, ?string $direction): JsonResponse
    {
        $data = $this->cartRetrievalService
            ->getCartData($sortBy, $direction);

        return response()->json([
            'success' => true,
            'products' => $data['products'],
        ]);
    }
}
