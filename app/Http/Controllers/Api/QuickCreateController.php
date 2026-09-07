<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Finance\Bank\BankAccountDTO;
use App\DTOs\Purchases\Supplier\SupplierDTO;
use App\DTOs\Sales\Customer\CustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Bank\StoreBankAccountRequest;
use App\Http\Requests\Purchases\Supplier\StoreSupplierRequest;
use App\Http\Requests\Sales\Customer\StoreCustomerRequest;
use App\Services\Finance\Bank\BankAccountService;
use App\Services\Purchases\Supplier\SupplierService;
use App\Services\Sales\Customer\CustomerService;
use Illuminate\Http\JsonResponse;

class QuickCreateController extends Controller
{
    public function __construct(
        private readonly BankAccountService $bankAccountService,
        private readonly CustomerService $customerService,
        private readonly SupplierService $supplierService,
    ) {}

    public function storeBankAccount(StoreBankAccountRequest $request): JsonResponse
    {
        $bankAccount = $this->bankAccountService->create(
            BankAccountDTO::fromArray($request->validated())
        );

        return response()->json($bankAccount, 201);
    }

    public function storeCustomer(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create(
            CustomerDTO::fromArray($request->validated())
        );

        return response()->json($customer, 201);
    }

    public function storeSupplier(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create(
            SupplierDTO::fromArray($request->validated())
        );

        return response()->json($supplier, 201);
    }
}
