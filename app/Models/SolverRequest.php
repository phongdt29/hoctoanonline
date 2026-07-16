<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SPEC §2.8 + §3.4 — Solver, chong le thuoc dap an.
 *
 * CLAUDE.md quy tac #6: KHONG BAO GIO tra dap an cuoi o lan goi dau.
 * Thu tu: hint mo -> hint sau (max 2) -> full solution khi nguoi dung bam.
 */
class SolverRequest extends Model
{
    use HasFactory;

    public const INPUT_TEXT  = 'text';
    public const INPUT_IMAGE = 'image';

    protected $fillable = [
        'student_id', 'input_type', 'problem_text', 'image_url',
        'ocr_confidence', 'hint_count', 'solution_revealed',
    ];

    protected function casts(): array
    {
        return [
            'ocr_confidence'    => 'decimal:2',
            'hint_count'        => 'integer',
            'solution_revealed' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Con duoc xin them hint khong — nguong tu config (khong hardcode 2). */
    public function canRequestMoreHint(): bool
    {
        return $this->hint_count < config('hoctoan.solver.max_hints');
    }

    /** OCR co du tin cay khong; neu khong -> bat student confirm de da parse. */
    public function needsOcrConfirmation(): bool
    {
        return $this->input_type === self::INPUT_IMAGE
            && $this->ocr_confidence !== null
            && $this->ocr_confidence < config('hoctoan.solver.ocr_min_confidence');
    }
}
