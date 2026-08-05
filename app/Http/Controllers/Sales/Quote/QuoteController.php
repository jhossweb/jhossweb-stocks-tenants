<?php

namespace App\Http\Controllers\Sales\Quote;

use App\Actions\Sales\Quote\ConvertToSaleAction;
use App\Actions\Sales\Quote\ValidateQuoteStockAction;
use App\DTOs\Sales\Quote\QuoteDTO;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Quote\ConvertQuoteRequest;
use App\Http\Requests\Sales\Quote\StoreQuoteRequest;
use App\Http\Requests\Sales\Quote\UpdateQuoteRequest;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Models\Warehouse;
use App\Repositories\Sales\Quote\QuoteRepository;
use App\Services\Sales\Quote\QuoteService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuoteController extends Controller
{
    public function __construct(
        protected QuoteRepository $quoteRepository,
        protected QuoteService $quoteService,
        protected ConvertToSaleAction $convertToSale,
        protected ValidateQuoteStockAction $validateStock,
    ) {}

    public function index()
    {
        $quotes = $this->quoteRepository->paginate(10);

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
        ]);
    }

    public function create()
    {
        return Inertia::render('Quotes/Create', $this->formData());
    }

    public function store(StoreQuoteRequest $request)
    {
        try {
            DB::beginTransaction();

            $quote = $this->quoteService->create(QuoteDTO::fromArray($request->validated()));

            DB::commit();

            return redirect()->route('quotes.index')->with('success', "Cotización {$quote->sls_number} creada exitosamente.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al guardar la cotización: '.$e->getMessage()]);
        }
    }

    public function show(SaleOrder $quote)
    {
        return redirect()->route('quotes.edit', $quote->id);
    }

    public function edit(SaleOrder $quote)
    {
        if ($quote->sls_trans_type_code !== 'COT') {
            abort(404);
        }

        return Inertia::render('Quotes/Edit', array_merge($this->formData(), [
            'quote' => $this->quoteRepository->findWithData($quote->id),
            'quote_availability' => $this->validateStock->handle($quote),
        ]));
    }

    public function update(UpdateQuoteRequest $request, SaleOrder $quote)
    {
        if ($quote->sls_trans_type_code !== 'COT') {
            abort(404);
        }

        try {
            DB::beginTransaction();

            $this->quoteService->update($quote, QuoteDTO::fromArray($request->validated()));

            DB::commit();

            return redirect()->route('quotes.index')->with('success', 'Cotización actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la cotización: '.$e->getMessage()]);
        }
    }

    public function destroy(SaleOrder $quote)
    {
        if ($quote->sls_trans_type_code !== 'COT') {
            abort(404);
        }

        try {
            DB::beginTransaction();

            $quote->movements()->delete();
            $quote->delete();

            DB::commit();

            return redirect()->route('quotes.index')->with('success', 'Cotización eliminada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al eliminar la cotización: '.$e->getMessage()]);
        }
    }

    public function convert(ConvertQuoteRequest $request, SaleOrder $quote)
    {
        if ($quote->sls_trans_type_code !== 'COT') {
            abort(404);
        }

        try {
            DB::beginTransaction();

            $sale = $this->convertToSale->execute($quote, $request->validated());

            DB::commit();

            return redirect()->route('sales.edit', $sale->id)->with('success', 'Cotización convertida a factura exitosamente.');
        } catch (InsufficientStockException $e) {
            DB::rollBack();

            return back()
                ->with('quote_availability', ['ok' => false, 'missing' => $e->details])
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al convertir la cotización: '.$e->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'customers' => Customer::all(),
            'products' => $this->quoteRepository->getAvailableProducts(),
            'warehouses' => Warehouse::all(),
            'rates' => Rate::all()->map(function (Rate $rate) {
                return [
                    'rate_date' => optional($rate->rate_date)->format('Y-m-d'),
                    'rate_usd' => $rate->rate_usd,
                    'rate_cop' => $rate->rate_cop,
                    'rate_eur' => $rate->rate_eur,
                ];
            }),
            'latest_rate' => Rate::orderBy('rate_date', 'desc')->first(),
            'coins' => TypeCoin::values(),
            'ivas' => TypeIva::values(),
        ];
    }
}
