<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Student;
use App\Models\TutorConversation;
use App\Models\TutorMessage;

/**
 * Ticket I1 + SPEC §2 module 3 — AI Tutor Chat.
 *
 * Persona theo students.tutor_gender (thay/co) + trinh do tu classification.
 * Prompt inject: giong xung ho + do sau giai thich phu hop nang luc.
 * Frontend polling GET .../messages?after_id=; day chi lo phia server.
 */
class TutorService
{
    public function __construct(private readonly AiProviderService $ai) {}

    public function startConversation(Student $student, ?string $title = null): TutorConversation
    {
        return TutorConversation::create([
            'student_id' => $student->id,
            'title' => $title,
        ]);
    }

    /**
     * Hoc sinh gui tin -> luu tin hoc sinh + goi AI (persona) -> luu tin AI.
     * Tra ve tin nhan AI vua tao.
     */
    public function sendMessage(TutorConversation $conversation, string $content): TutorMessage
    {
        $student = $conversation->student;

        TutorMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => TutorMessage::SENDER_STUDENT,
            'content' => $content,
        ]);

        $reply = $this->ai->text(
            AiLog::FEATURE_TUTOR_CHAT,
            $this->buildPrompt($conversation, $content),
            $student->id,
        );

        $aiMessage = TutorMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => TutorMessage::SENDER_AI,
            'content' => $reply,
        ]);

        $conversation->touch();

        return $aiMessage;
    }

    private function buildPrompt(TutorConversation $conversation, string $content): string
    {
        $student = $conversation->student;

        $persona = $student->tutor_gender === 'co'
            ? 'một CÔ giáo toán thân thiện, xưng "cô" và gọi học sinh là "em"'
            : 'một THẦY giáo toán thân thiện, xưng "thầy" và gọi học sinh là "em"';

        $level = $student->latestClassification?->final_level ?? $student->self_assessed_level ?? 'kha';
        $levelHint = match ($level) {
            'trung_binh' => 'Giải thích thật chậm, chia nhỏ từng bước, dùng ví dụ gần gũi.',
            'gioi' => 'Có thể đi nhanh, gợi mở tư duy nâng cao, thử thách thêm.',
            default => 'Giải thích rõ ràng, vừa phải, kèm ví dụ.',
        };

        // Lich su gan day de giu ngu canh (gioi han de prompt khong qua dai).
        $history = $conversation->messages()
            ->latest('id')->limit(6)->get()->reverse()
            ->map(fn ($m) => ($m->sender === 'student' ? 'Học sinh' : 'Gia sư').': '.$m->content)
            ->implode("\n");

        return <<<PROMPT
        Bạn là {$persona}, đang kèm học sinh lớp {$student->grade}.
        {$levelHint}
        Chỉ trả lời về toán học và việc học. Nếu học sinh hỏi lạc đề, nhẹ nhàng kéo về bài học.

        Lịch sử trò chuyện gần đây:
        {$history}

        Câu hỏi mới của học sinh: {$content}

        Trả lời ngắn gọn, đúng persona.
        PROMPT;
    }
}
