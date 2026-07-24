<?php

namespace App\Services\Ai;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vuot han muc goi AI trong ngay cua 1 hoc sinh.
 *
 * render() tra 429 + JSON message -> app.js (handler 429 toan cuc) tu hien toast.
 * Ke thua AiException nen cac catch(AiException) san co van bat duoc.
 */
class DailyLimitException extends AiException
{
    public function render(Request $request): ?JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 429);
        }

        return null;   // web request: de Laravel xu ly mac dinh
    }
}
