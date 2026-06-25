<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ApiNumberResource;
use App\Models\Company;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NumberController extends Controller
{
    /**
     * GET /api/v1/numbers — números disponíveis para a empresa escolher.
     * Os números pertencem à plataforma; se houver números atribuídos à
     * empresa, devolve apenas esses, caso contrário devolve o pool todo.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('company');

        // Números dedicados à empresa + pool partilhado (sem dono).
        $numbers = Device::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('company_id', $company->id)->orWhereNull('company_id'))
            ->get();

        return ApiNumberResource::collection($numbers)->response();
    }
}
