<?php

namespace App\Services;

use App\Clients\CartServiceClient;
use App\Clients\ProductServiceClient;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderStatusException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\ProductNotFoundException;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private CartServiceClient $cartClient,
        private ProductServiceClient $productClient
    ) {}

    /**
     * Alur checkout untuk membuat pesanan baru dari isi keranjang user:
     * 1. Ambil data keranjang dari cart-service.
     * 2. Periksa aturan bisnis: keranjang tidak boleh kosong.
     * 3. Ambil data produk (nama, harga, stok) dari product-service untuk setiap item.
     * 4. Periksa ketersediaan produk dan kecukupan stok.
     * 5. Simpan pesanan beserta item-itemnya secara atomik dalam DB::transaction (snapshot harga & nama).
     * 6. Minta cart-service mengosongkan keranjang (jika gagal, pesanan tetap 201).
     *
     * @param int $userId
     * @return Order
     * @throws EmptyCartException
     * @throws ProductNotFoundException
     * @throws InsufficientStockException
     */
    public function checkout(int $userId): Order
    {
        // 1. Membaca isi keranjang user dari cart-service
        $cart = $this->cartClient->getCart($userId);
        $cartItems = $cart['items'] ?? [];

        // 2. Aturan bisnis: Keranjang tidak boleh kosong
        if (empty($cartItems)) {
            throw new EmptyCartException("Keranjang belanja kosong");
        }

        $validatedItems = [];
        $totalAmount = 0;

        // 3 & 4. Periksa setiap produk dan kecukupan stok ke product-service
        foreach ($cartItems as $cartItem) {
            $productId = (int) $cartItem['product_id'];
            $requestedQty = (int) ($cartItem['quantity'] ?? 1);

            $product = $this->productClient->getProduct($productId);

            if (! $product) {
                throw new ProductNotFoundException("Produk dengan id {$productId} tidak ditemukan di katalog");
            }

            $availableStock = (int) ($product['stock'] ?? 0);
            if ($requestedQty > $availableStock) {
                throw new InsufficientStockException(
                    $product['name'] ?? "Produk #{$productId}",
                    $requestedQty,
                    $availableStock
                );
            }

            // Ubah harga string teks ("450000.00") menjadi angka integer/numeric
            $unitPrice = (int) round((float) ($product['price'] ?? 0));
            $subtotal = $unitPrice * $requestedQty;
            $totalAmount += $subtotal;

            $validatedItems[] = [
                'product_id' => $productId,
                'product_name' => (string) ($product['name'] ?? ''),
                'unit_price' => $unitPrice,
                'quantity' => $requestedQty,
                'subtotal' => $subtotal,
            ];
        }

        // 5. Simpan pesanan beserta snapshot item-itemnya dalam DB::transaction
        $order = DB::transaction(function () use ($userId, $validatedItems, $totalAmount) {
            $newOrder = Order::create([
                'user_id' => $userId,
                'status' => Order::STATUS_PENDING,
                'total_amount' => $totalAmount,
            ]);

            foreach ($validatedItems as $itemData) {
                $newOrder->items()->create($itemData);
            }

            return $newOrder;
        });

        // 6. Minta cart-service mengosongkan keranjang
        // Jika gagal dikosongkan, pesanan tidak boleh dibatalkan, cukup dicatat di log
        $this->cartClient->clearCart($userId);

        return $order->load('items');
    }

    /**
     * Mengambil detail satu pesanan beserta relasi item-itemnya.
     *
     * @param int $orderId
     * @return Order
     * @throws OrderNotFoundException
     */
    public function getOrder(int $orderId): Order
    {
        $order = Order::with('items')->find($orderId);

        if (! $order) {
            throw new OrderNotFoundException($orderId);
        }

        return $order;
    }

    /**
     * Mengambil daftar pesanan satu user (terbaru di atas), dengan filter status opsional.
     *
     * @param int $userId
     * @param string|null $status
     * @return Collection
     */
    public function getUserOrders(int $userId, ?string $status = null): Collection
    {
        $query = Order::with('items')
            ->where('user_id', $userId)
            ->latest();

        if ($status !== null && $status !== '') {
            $query->where('status', strtoupper($status));
        }

        return $query->get();
    }

    /**
     * Melakukan simulasi pembayaran: status PENDING diubah menjadi PAID.
     *
     * @param int $orderId
     * @return Order
     * @throws OrderNotFoundException
     * @throws InvalidOrderStatusException
     */
    public function payOrder(int $orderId): Order
    {
        $order = $this->getOrder($orderId);

        if ($order->status !== Order::STATUS_PENDING) {
            throw new InvalidOrderStatusException($order->status);
        }

        $order->update(['status' => Order::STATUS_PAID]);

        return $order->fresh(['items']);
    }
}
