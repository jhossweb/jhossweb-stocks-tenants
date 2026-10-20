<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Tenancy;

use App\Actions\Tenancy\RecordPaymentAction;
use App\Actions\Tenancy\VoidPaymentAction;
use App\Enum\Tenancy\Currency;
use App\Enum\Tenancy\PaymentMethod;
use App\Enum\Tenancy\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenancy\Payment\StorePaymentRequest;
use App\Http\Requests\Admin\Tenancy\Payment\VoidPaymentRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Repositories\Tenancy\BillingRateRepository;
use App\Repositories\Tenancy\SubscriptionPaymentRepository;
use App\Repositories\Tenancy\SubscriptionRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        private readonly SubscriptionPaymentRepository $repository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly BillingRateRepository $billingRateRepository,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'tenant_id' => $request->get('tenant_id'),
        ];

        $payments = $this->repository->paginate(
            $filters,
            (int) $request->get('per_page', 15),
            $request->get('sort'),
            (string) $request->get('direction', 'desc'),
        )->through(fn (SubscriptionPayment $payment): array => $this->present($payment));

        return Inertia::render('admin/payments/index', [
            'payments' => $payments,
            'filters' => $filters,
            'totalPaidUsd' => $this->repository->totalPaidUsd(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/payments/create', [
            'subscriptions' => $this->subscriptionOptions(),
            'latestRate' => $this->billingRateRepository->latest()?->toArray(),
            'selectedSubscriptionId' => $request->get('subscription_id'),
            'currencies' => array_map(fn (Currency $c): string => $c->value, Currency::cases()),
            'methods' => array_map(fn (PaymentMethod $m): string => $m->value, PaymentMethod::cases()),
        ]);
    }

    public function store(StorePaymentRequest $request, RecordPaymentAction $action): RedirectResponse
    {
        $subscription = Subscription::query()->findOrFail($request->validated('subscription_id'));
        $payment = $action->execute($subscription, $request->validated(), $request->user()?->id);

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', "Cobro {$payment->receipt_number} registrado.");
    }

    public function show(SubscriptionPayment $payment): Response
    {
        return Inertia::render('admin/payments/show', [
            'payment' => $this->present($payment->load(['subscription.plan', 'tenant', 'recordedBy', 'voidedBy'])),
        ]);
    }

    public function void(VoidPaymentRequest $request, SubscriptionPayment $payment, VoidPaymentAction $action): RedirectResponse
    {
        $action->execute($payment, $request->validated('reason'), $request->user()?->id);

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', "Cobro {$payment->receipt_number} anulado.");
    }

    public function receipt(SubscriptionPayment $payment): SymfonyResponse
    {
        $payment->load(['subscription.plan', 'tenant', 'recordedBy']);

        $pdf = Pdf::loadView('billing.receipt', ['payment' => $payment])
            ->setPaper('a6', 'portrait');

        return $pdf->download("recibo-{$payment->receipt_number}.pdf");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function subscriptionOptions(): array
    {
        return $this->subscriptionRepository->all()
            ->map(fn (Subscription $subscription): array => [
                'id' => $subscription->id,
                'label' => ($subscription->tenant?->name ?? $subscription->tenant_id)
                    .' · '.($subscription->plan?->name ?? 'Sin plan')
                    .' · '.$subscription->status,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SubscriptionPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'receipt_number' => $payment->receipt_number,
            'subscription_id' => $payment->subscription_id,
            'tenant_id' => $payment->tenant_id,
            'tenant_name' => $payment->tenant?->name,
            'plan_name' => $payment->subscription?->plan?->name,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'exchange_rate' => $payment->exchange_rate,
            'amount_usd' => $payment->amount_usd,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'paid_at' => $payment->paid_at?->toDateString(),
            'status' => $payment->status,
            'recorded_by' => $payment->recordedBy?->name,
            'voided_at' => $payment->voided_at?->toDateTimeString(),
            'voided_by' => $payment->voidedBy?->name,
            'is_voided' => $payment->isVoided(),
            'notes' => $payment->notes,
            'created_at' => $payment->created_at?->toDateTimeString(),
            'statuses' => array_map(fn (PaymentStatus $s): string => $s->value, PaymentStatus::cases()),
        ];
    }
}
