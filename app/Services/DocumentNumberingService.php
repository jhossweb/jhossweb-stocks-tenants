<?php

namespace App\Services;

use App\Models\TransactionType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberingService
{
    /**
     * Genera el siguiente número para un tipo de transacción.
     * Ej: COT-0001, COT-0002 ...
     */
    public function nextNumber(string $code): string
    {
        return DB::transaction(function () use ($code) {
            /** @var TransactionType|null $transactionType */
            $transactionType = TransactionType::lockForUpdate()
                ->where('trans_type_code', $code)
                ->first();

            if (! $transactionType) {
                throw new RuntimeException("Tipo de transacción {$code} no encontrado.");
            }

            $next = (int) $transactionType->trans_last_number;

            $transactionType->update(['trans_last_number' => $next + 1]);

            return $this->format($code, $next);
        });
    }

    private function format(string $code, int $number): string
    {
        return sprintf('%s-%04d', $code, $number);
    }
}
