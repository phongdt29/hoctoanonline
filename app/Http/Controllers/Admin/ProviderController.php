<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Services\Ai\GeminiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quan ly registry AI provider tu giao dien (ticket T3 — ban web).
 *
 * Key KHONG BAO GIO hien full: chi maskedApiKey(). Cap nhat key la optional —
 * de trong khi sua = giu key cu.
 */
class ProviderController extends Controller
{
    public function index(): View
    {
        return view('admin.providers', [
            'providers' => AiProvider::orderBy('priority')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, keyRequired: true);

        $provider = new AiProvider([
            'name'     => $data['name'],
            'base_url' => $data['base_url'],
            'models'   => ['default' => $data['model']],
            'status'   => $data['status'],
            'priority' => $data['priority'],
        ]);
        $provider->setApiKey($data['api_key']);
        $provider->save();

        return back()->with('status', "Đã thêm nhà cung cấp \"{$provider->name}\".");
    }

    public function update(Request $request, AiProvider $provider): RedirectResponse
    {
        $data = $this->validated($request, keyRequired: false);

        $provider->fill([
            'name'     => $data['name'],
            'base_url' => $data['base_url'],
            'models'   => ['default' => $data['model']],
            'status'   => $data['status'],
            'priority' => $data['priority'],
        ]);

        // Chi doi key khi admin nhap moi — de trong = giu key cu.
        if (! empty($data['api_key'])) {
            $provider->setApiKey($data['api_key']);
        }

        $provider->save();

        return back()->with('status', "Đã cập nhật \"{$provider->name}\".");
    }

    public function destroy(AiProvider $provider): RedirectResponse
    {
        $name = $provider->name;
        $provider->delete();

        return back()->with('status', "Đã xoá \"{$name}\".");
    }

    /** Ping nhanh: goi 1 prompt cuc ngan de kiem tra key con song. */
    public function test(AiProvider $provider, GeminiClient $client): RedirectResponse
    {
        try {
            $result = $client->generate($provider, 'Trả lời đúng một từ: OK', null);
            $text = trim($result['text']) ?: '(rỗng)';

            return back()->with('status', "✅ Key \"{$provider->name}\" hoạt động. Phản hồi: {$text}");
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // Rut gon thong bao loi dai (vd JSON body Gemini) cho de doc.
            $short = mb_substr($msg, 0, 180);

            return back()->with('error', "❌ Key \"{$provider->name}\" lỗi: {$short}");
        }
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, bool $keyRequired): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'base_url' => ['required', 'url', 'max:255'],
            'model'    => ['required', 'string', 'max:100'],
            'api_key'  => [$keyRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'status'   => ['required', 'in:active,disabled'],
            'priority' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
    }
}
