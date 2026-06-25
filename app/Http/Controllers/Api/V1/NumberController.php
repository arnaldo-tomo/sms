<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ApiNumberResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NumberController extends Controller
{
    /**
     * GET /api/v1/numbers — números (dispositivos) da empresa autenticada.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('company');

        return ApiNumberResource::collection(
            $company->devices()->get()
        )->response();
    }
}
