<?php

namespace App\Http\Controllers\Sales\SaleReturn;

use App\DTOs\Sales\SaleReturn\SaleReturnDTO;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaleReturn\StoreSaleReturnRequest;
use App\Http\Requests\Sales\SaleReturn\UpdateSaleReturnRequest;
use App\Models\Customer;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Models\Warehouse;
use App\Repositories\Sales\SaleReturn\SaleReturnRepository;
use App\Services\Sales\SaleReturn\SaleReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnService $saleReturnService,
        protected SaleReturnRepository $saleReturnRepository,
    ) {}

    public function index(): Response
    {
        $returns = SaleOrder::with(['customer', 'movements.product', 'movements.warehouse'])
            ->where('sls_trans_type_code', 'DEVV')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('SaleReturns/Index', [
            'returns' => $returns,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SaleReturns/Create', [
            'customers' => Customer::active()->get(),
            'products' => $this->saleReturnRepository->getAvailableProducts(),
            'warehouses' => Warehouse::active()->get(),
            'transactionTypes' => TransactionType::all(),
            'rates' => $this->saleReturnRepository->getRatesForSelect(),
            'latest_rate' => $this->saleReturnRepository->getLatestRate(),
            'coins' => TypeCoin::values(),
            'ivas' => TypeIva::values(),
        ]);
    }

    public function store(StoreSaleReturnRequest $request)
    {
        $dto = SaleReturnDTO::fromArray($request->validated());

        try {
            $this->saleReturnService->store($dto);

            return redirect()->route('sale-returns.index')
                ->with('success', 'Devolución guardada exitosamente.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(SaleOrder $saleReturn)
    {
        return redirect()->route('sale-returns.edit', $saleReturn->id);
    }

    public function edit(int $id): Response
    {
        $return = SaleOrder::with(['movements.product', 'movements.warehouse', 'customer', 'transactionType'])
            ->findOrFail($id);

        return Inertia::render('SaleReturns/Edit', [
            'saleReturn' => $return,
            'customers' => Customer::active()->get(),
            'products' => $this->saleReturnRepository->getAvailableProducts(),
            'warehouses' => Warehouse::active()->get(),
            'transactionTypes' => TransactionType::all(),
            'rates' => $this->saleReturnRepository->getRatesForSelect(),
            'latest_rate' => $this->saleReturnRepository->getLatestRate(),
            'coins' => TypeCoin::values(),
            'ivas' => TypeIva::values(),
        ]);
    }

    public function update(UpdateSaleReturnRequest $request, int $id)
    {
        $return = SaleOrder::findOrFail($id);
        $dto = SaleReturnDTO::fromArray($request->validated());

        try {
            $this->saleReturnService->update($return, $dto);

            return redirect()->route('sale-returns.index')
                ->with('success', 'Devolución actualizada exitosamente.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(int $id)
    {
        $return = SaleOrder::findOrFail($id);

        try {
            $this->saleReturnService->destroy($return);

            return redirect()->route('sale-returns.index')
                ->with('success', 'Devolución eliminada exitosamente.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => 'Ocurrió un error al eliminar la devolución: '.$e->getMessage()]);
        }
    }

    public function getUnpaidSales(Customer $customer, Request $request): JsonResponse
    {
        try {
            $excludeReturnId = $request->integer('exclude_return_id', 0) ?: null;

            return response()->json(
                $this->saleReturnRepository->findUnpaidSalesByCustomer($customer->id, $excludeReturnId)
            );
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Error al obtener facturas pendientes: '.$e->getMessage()], 500
            );
        }
    }

    public function getSaleOrderByNumber(string $sls_number): JsonResponse
    {
        try {
            $sale = SaleOrder::with(['movements', 'customer'])
                ->where('sls_number', $sls_number)
                ->where('sls_trans_type_code', 'FACV')
                ->first();

            if (! $sale) {
                return response()->json(['error' => 'Factura de venta no encontrada'], 404);
            }

            return response()->json($sale);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Error al obtener factura: '.$e->getMessage()], 500
            );
        }
    }

    public function getSaleOrderItems(SaleOrder $saleOrder, Request $request): JsonResponse
    {
        try {
            $excludeReturnId = $request->integer('exclude_return_id', 0) ?: null;

            return response()->json(
                $this->saleReturnRepository->getSaleOrderItems($saleOrder, $excludeReturnId)
            );
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Error al obtener items: '.$e->getMessage()], 500
            );
        }
    }
}
