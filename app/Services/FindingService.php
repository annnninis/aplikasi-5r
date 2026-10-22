<?php

namespace App\Services;

use App\Enums\FindingPhotoType;
use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Models\Area;
use App\Models\Finding;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat transisi status temuan (PRD MVP §4.2).
 */
class FindingService
{
    public const PHOTO_DISK = 'public';

    /**
     * Lapor Cepat: siapa pun membuat temuan baru dengan satu foto "sebelum".
     */
    public function report(User $reporter, Area $area, string $description, FindingPriority $priority, UploadedFile $photo): Finding
    {
        if (! $area->is_active) {
            throw ValidationException::withMessages(['area_id' => 'Area ini sudah tidak aktif.']);
        }

        return DB::transaction(function () use ($reporter, $area, $description, $priority, $photo) {
            $finding = Finding::create([
                'source' => 'quick_report',
                'area_id' => $area->id,
                'reported_by' => $reporter->id,
                'description' => $description,
                'priority' => $priority,
                'status' => FindingStatus::BelumDitugaskan,
            ]);

            $finding->photos()->create([
                'type' => FindingPhotoType::Before,
                'photo_path' => $photo->store("findings/{$finding->id}", self::PHOTO_DISK),
                'uploaded_by' => $reporter->id,
            ]);

            return $finding;
        });
    }

    /**
     * Tunjuk (atau ganti) PIC. Deadline dihitung dari waktu penugasan sesuai prioritas.
     */
    public function assign(Finding $finding, User $pic, FindingPriority $priority): Finding
    {
        $this->ensureStatus($finding, [FindingStatus::BelumDitugaskan, FindingStatus::BelumSelesai, FindingStatus::Terlambat]);

        if (! $pic->is_active) {
            throw ValidationException::withMessages(['pic_id' => 'PIC harus pengguna yang aktif.']);
        }

        $now = now();

        $finding->update([
            'pic_id' => $pic->id,
            'priority' => $priority,
            'status' => FindingStatus::BelumSelesai,
            'assigned_at' => $now,
            'deadline' => $now->copy()->addDays($priority->deadlineDays())->toDateString(),
        ]);

        return $finding;
    }

    /**
     * Tolak laporan yang tidak valid (mis. duplikat atau bukan masalah 5R).
     */
    public function reject(Finding $finding, string $reason): Finding
    {
        $this->ensureStatus($finding, [FindingStatus::BelumDitugaskan]);

        $finding->update([
            'status' => FindingStatus::Ditolak,
            'rejection_reason' => $reason,
        ]);

        return $finding;
    }

    /**
     * @param  list<FindingStatus>  $allowed
     */
    private function ensureStatus(Finding $finding, array $allowed): void
    {
        if (! in_array($finding->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Temuan berstatus \"{$finding->status->getLabel()}\" tidak bisa diproses dengan aksi ini.",
            ]);
        }
    }
}
