<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use Illuminate\View\View;

/**
 * Cong cu uoc tinh chi phi token AI — nhap so user + so luot goi -> ra token + tien.
 *
 * Token trung binh moi luot lay tu ai_logs THAT (mac dinh, van sua duoc):
 *   input  = prompt_tokens
 *   output = max(completion_tokens, total_tokens - prompt_tokens)   (tinh ca thinking tokens)
 * Gia lay tu config('hoctoan.ai_pricing'). Day la UOC TINH, khong phai hoa don.
 */
class TokenCostController extends Controller
{
    public function index(): View
    {
        $pricing = config('hoctoan.ai_pricing');

        // Token trung binh THAT tren moi luot goi (chi tinh log co token).
        $overall = AiLog::where('total_tokens', '>', 0)
            ->selectRaw('COUNT(*) c, AVG(prompt_tokens) ai, AVG(GREATEST(completion_tokens, total_tokens - prompt_tokens)) ao')
            ->first();

        $hasData  = (int) ($overall->c ?? 0) > 0;
        $avgInput  = $hasData ? (int) round($overall->ai) : 800;    // fallback khi chua co du lieu
        $avgOutput = $hasData ? (int) round($overall->ao) : 1500;

        // Tham chieu theo tinh nang (giup admin uoc luong so luot tung loai).
        $byFeature = AiLog::where('total_tokens', '>', 0)
            ->selectRaw('feature, COUNT(*) c, AVG(prompt_tokens) ai, AVG(GREATEST(completion_tokens, total_tokens - prompt_tokens)) ao')
            ->groupBy('feature')
            ->orderByDesc('c')
            ->get()
            ->map(fn ($r) => [
                'feature' => $r->feature,
                'calls'   => (int) $r->c,
                'input'   => (int) round($r->ai),
                'output'  => (int) round($r->ao),
            ]);

        return view('admin.token-cost', [
            'pricing'   => $pricing,
            'avgInput'  => $avgInput,
            'avgOutput' => $avgOutput,
            'hasData'   => $hasData,
            'sampleCalls' => (int) ($overall->c ?? 0),
            'byFeature' => $byFeature,
        ]);
    }
}
