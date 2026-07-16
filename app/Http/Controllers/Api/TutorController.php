<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TutorConversation;
use App\Services\TutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Ticket I1 — API Tutor chat. Polling after_id. */
class TutorController extends Controller
{
    public function __construct(private readonly TutorService $tutor) {}

    /**
     * GET /api/v1/tutor/current — cuoc tro chuyen gan nhat + lich su.
     * Get-or-create: chua co thi tao 1 cai moi. Dung khi mo trang de load lich su
     * (truoc day moi lan mo trang lai tao conversation moi -> mat lich su).
     */
    public function current(Request $request): JsonResponse
    {
        $student = $this->student($request);

        $conversation = $student->tutorConversations()->latest('updated_at')->first()
            ?? $this->tutor->startConversation($student);

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'sender' => $m->sender,
                'content' => $m->content,
                'time' => $m->created_at->timezone('Asia/Ho_Chi_Minh')->format('H:i'),
            ]);

        return response()->json([
            'data' => [
                'conversation_id' => $conversation->id,
                'messages' => $messages,
            ],
            'message' => 'OK',
        ]);
    }

    /** POST /api/v1/tutor/conversations */
    public function createConversation(Request $request): JsonResponse
    {
        $student = $this->student($request);

        $conversation = $this->tutor->startConversation(
            $student,
            $request->input('title'),
        );

        return response()->json([
            'data' => ['id' => $conversation->id],
            'message' => 'Đã tạo cuộc trò chuyện.',
        ]);
    }

    /** POST /api/v1/tutor/conversations/{conversation}/messages */
    public function sendMessage(Request $request, TutorConversation $conversation): JsonResponse
    {
        $this->authorizeOwner($request, $conversation);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $aiMessage = $this->tutor->sendMessage($conversation, $data['content']);

        return response()->json([
            'data' => [
                'id' => $aiMessage->id,
                'sender' => $aiMessage->sender,
                'content' => $aiMessage->content,
            ],
            'message' => 'OK',
        ]);
    }

    /** GET /api/v1/tutor/conversations/{conversation}/messages?after_id= (polling 3s) */
    public function messages(Request $request, TutorConversation $conversation): JsonResponse
    {
        $this->authorizeOwner($request, $conversation);

        $messages = $conversation->messages()
            ->afterId($request->integer('after_id') ?: null)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'sender' => $m->sender,
                'content' => $m->content,
                'time' => $m->created_at->timezone('Asia/Ho_Chi_Minh')->format('H:i'),
            ]);

        return response()->json(['data' => $messages, 'message' => 'OK']);
    }

    private function student(Request $request): \App\Models\Student
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);

        return $student;
    }

    private function authorizeOwner(Request $request, TutorConversation $conversation): void
    {
        abort_unless(
            $conversation->student_id === $request->user()->student?->id,
            403,
        );
    }
}
