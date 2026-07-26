<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\FinanceTransactionResource;
use App\Models\FinanceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinanceTransactionApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = FinanceTransaction::query();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($accountId = $request->query('account_id')) {
            $query->where('account_id', $accountId);
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $transactions = $query->latest('date')->paginate($request->query('per_page', 15));

        return $this->successResponse(FinanceTransactionResource::collection($transactions)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'description' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'category_id' => ['nullable', 'exists:finance_categories,id'],
            'date' => ['nullable', 'date'],
        ]);

        $validated['date'] = $validated['date'] ?? now();

        $transaction = FinanceTransaction::create($validated);

        return $this->successResponse(new FinanceTransactionResource($transaction), 'Transaction created successfully', Response::HTTP_CREATED);
    }

    public function destroy(FinanceTransaction $transaction): JsonResponse
    {
        $transaction->delete();

        return $this->successResponse(null, 'Transaction deleted successfully');
    }
}
