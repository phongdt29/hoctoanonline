<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ticket T3 — CRUD AI provider cho admin.
 *
 * api_key: Crypt::encrypt luc luu, KHONG BAO GIO tra ra response (masked).
 * Doi priority / bat-tat de dieu khien failover.
 */
class AdminProviderController extends Controller
{
    /** GET /api/v1/admin/ai-providers */
    public function index(): JsonResponse
    {
        $providers = AiProvider::orderBy('priority')->get()->map(fn ($p) => $this->present($p));

        return response()->json(['data' => $providers, 'message' => 'OK']);
    }

    /** POST /api/v1/admin/ai-providers */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        $provider = new AiProvider([
            'name' => $data['name'],
            'base_url' => $data['base_url'],
            'models' => $data['models'],
            'status' => $data['status'],
            'priority' => $data['priority'],
        ]);
        $provider->setApiKey($data['api_key']);   // Crypt::encrypt ben trong
        $provider->save();

        return response()->json(['data' => $this->present($provider), 'message' => 'Đã thêm provider.'], 201);
    }

    /** PUT /api/v1/admin/ai-providers/{provider} */
    public function update(Request $request, AiProvider $provider): JsonResponse
    {
        $data = $this->validateData($request, updating: true);

        $provider->fill([
            'name' => $data['name'],
            'base_url' => $data['base_url'],
            'models' => $data['models'],
            'status' => $data['status'],
            'priority' => $data['priority'],
        ]);

        // Chi doi key khi co gui key moi (khong bat nhap lai key moi lan sua).
        if (! empty($data['api_key'])) {
            $provider->setApiKey($data['api_key']);
        }

        $provider->save();

        return response()->json(['data' => $this->present($provider), 'message' => 'Đã cập nhật.']);
    }

    /** DELETE /api/v1/admin/ai-providers/{provider} */
    public function destroy(AiProvider $provider): JsonResponse
    {
        $provider->delete();

        return response()->json(['data' => null, 'message' => 'Đã xóa provider.']);
    }

    private function validateData(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'base_url' => ['required', 'url', 'max:255'],
            // Them moi bat buoc co key; sua thi khong (giu key cu).
            'api_key' => [$updating ? 'nullable' : 'required', 'string'],
            'models' => ['required', 'array'],
            'status' => ['required', Rule::in([AiProvider::STATUS_ACTIVE, AiProvider::STATUS_DISABLED])],
            'priority' => ['required', 'integer', 'min:1', 'max:255'],
        ]);
    }

    /** KHONG BAO GIO tra api_key that — chi masked. */
    private function present(AiProvider $provider): array
    {
        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'base_url' => $provider->base_url,
            'models' => $provider->models,
            'status' => $provider->status,
            'priority' => $provider->priority,
            'api_key_masked' => $provider->maskedApiKey(),
        ];
    }
}
