<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Shop\Order;

use App\Http\Requests\API\Shop\Order\OrderRequest;
use App\Http\Resources\Shop\OrderResource;
use Domain\Shop\Branch\Models\Branch;
use Domain\Shop\Order\Actions\CreateOrderAction;
use Domain\Shop\Order\DataTransferObjects\OrderData;
use Domain\Shop\Order\Enums\ClaimType;
use Domain\Shop\Order\Enums\PaymentMethod;
use Domain\Shop\Order\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Throwable;

/**
 * @tags Order
 */
#[Middleware('auth:sanctum')]
class OrderController
{
    /** @throws Throwable */
    #[Post('branches/{enabledBranch}/orders', 'orders.store')]
    public function __invoke(OrderRequest $request, Branch $enabledBranch): OrderResource
    {
        /** @var \Domain\Shop\Customer\Models\Customer $customer */
        $customer = Auth::user();

        $data = $request->validated();

        $order = DB::transaction(
            fn () => app(CreateOrderAction::class)
                ->execute(new OrderData(
                    branch: $enabledBranch,
                    customer: $customer,
                    payment_method: PaymentMethod::from($data['payment_method']),
                    claimType: ClaimType::from($data['claim_type']),
                    claim_at: now()->parse($data['claim_at'])->timezone($customer->timezone),
                    notes: $data['notes'] ?? null,
                ))
        );

        return OrderResource::make($order);
    }
}
