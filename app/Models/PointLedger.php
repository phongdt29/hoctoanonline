<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * SPEC §2.8 — so cai diem thuong. APPEND-ONLY.
 *
 * CLAUDE.md quy tac #5: khong duoc ton tai bat ky code path update/delete nao.
 * Ghi sai thi ghi BUT TOAN NGUOC (amount am, reason=admin_adjustment),
 * khong bao gio sua ban ghi cu.
 *
 * Chan o 3 lop:
 *  1. update()/delete()/forceDelete() nem RuntimeException.
 *  2. Event `updating`/`deleting` nem RuntimeException — chan ca duong di khac
 *     (vd: $model->save() sau khi doi thuoc tinh, hay Model::destroy()).
 *  3. Schema khong co updated_at.
 *
 * LUU Y: query builder (DB::table('point_ledger')->update(...)) KHONG di qua
 * Eloquent nen khong bi chan — cam dung, review PR phai bat.
 */
class PointLedger extends Model
{
    use HasFactory;

    protected $table = 'point_ledger';

    /** Chi co created_at, khong co updated_at (ban ghi khong bao gio doi). */
    public const UPDATED_AT = null;

    public const REASON_ASSESSMENT_COMPLETE = 'assessment_complete';
    public const REASON_QUIZ_SCORE          = 'quiz_score';
    public const REASON_ASSIGNMENT_GRADED   = 'assignment_graded';
    public const REASON_ADMIN_ADJUSTMENT    = 'admin_adjustment';

    protected $fillable = [
        'student_id', 'amount', 'reason', 'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'integer',
            'ref_id'     => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $ledger): void {
            throw new RuntimeException(
                'point_ledger la append-only: khong duoc sua ban ghi. '
                .'Muon dieu chinh thi ghi but toan nguoc voi reason=admin_adjustment.'
            );
        });

        static::deleting(function (self $ledger): void {
            throw new RuntimeException(
                'point_ledger la append-only: khong duoc xoa ban ghi.'
            );
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException(
            'point_ledger la append-only: khong duoc sua ban ghi. '
            .'Muon dieu chinh thi ghi but toan nguoc voi reason=admin_adjustment.'
        );
    }

    public function delete(): bool
    {
        throw new RuntimeException('point_ledger la append-only: khong duoc xoa ban ghi.');
    }

    public function forceDelete(): bool
    {
        throw new RuntimeException('point_ledger la append-only: khong duoc xoa ban ghi.');
    }
}
