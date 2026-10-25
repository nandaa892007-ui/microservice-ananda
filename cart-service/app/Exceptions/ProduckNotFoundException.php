<php?

namespace App\Exceptions;

Use RuntimeException

class ProduckNotFoundException extends RuntimeException
{
    public function __construct(int $productId)
    {
        parent::__construct("Produk dengan id {$productId} tidak ditemukan");
    }
}
