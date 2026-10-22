<?php

namespace App\Services;

use App\Enums\FindingPhotoType;
use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Models\Area;
use App\Models\Finding;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat transisi status temuan (PRD MVP §4.2). Setiap transisi juga memicu notifikasi.
 */
class FindingService
{
    public const PHOTO_DISK = 'public';

    public function __construct(private FindingNotifier $notifier) {}

    /**
     * Lapor Cepat: siapa pun membuat temuan baru dengan satu foto "sebelum".
     */
    public function report(User $reporter, Area $area, string $description, FindingPriority $priority, UploadedFile $photo): Finding
    {
        if (! $area->is_active) {
            throw ValidationException::withMessages(['area_id' => 'Area ini sudah tidak aktif.']);
        }

        $finding = DB::transaction(function () use ($reporter, $area, $description, $priority, $photo) {
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

        $this->notifier->reported($finding);

        return $finding;
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

        $this->notifier->assigned($finding->refresh());

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

        $this->notifier->rejected($finding);

        return $finding;
    }

    /**
     * PIC mengirim bukti perbaikan (foto "sesudah" + catatan). Tetap boleh walau sudah terlambat.
     */
    public function submitResolution(Finding $finding, User $pic, UploadedFile $photo, string $note): Finding
    {
        $this->ensureStatus($finding, [FindingStatus::BelumSelesai, FindingStatus::Terlambat]);

        if ($finding->pic_id !== $pic->id) {
            throw ValidationException::withMessages(['pic_id' => 'Hanya PIC temuan ini yang bisa mengirim tindak lanjut.']);
        }

        DB::transaction(function () use ($finding, $pic, $photo, $note) {
            $finding->photos()->create([
                'type' => FindingPhotoType::After,
                'photo_path' => $photo->store("findings/{$finding->id}", self::PHOTO_DISK),
                'uploaded_by' => $pic->id,
            ]);

            $finding->update([
                'status' => FindingStatus::MenungguVerifikasi,
                'resolution_note' => $note,
            ]);
        });

        $this->notifier->submitted($finding);

        return $finding;
    }

    /**
     * Koordinator/Admin menyatakan perbaikan sudah sesuai.
     */
    public function verify(Finding $finding, User $verifier): Finding
    {
        $this->ensureStatus($finding, [FindingStatus::MenungguVerifikasi]);

        $finding->update([
            'status' => FindingStatus::Selesai,
            'verified_by' => $verifier->id,
            'resolved_at' => now(),
            'revision_note' => null,
        ]);

        $this->notifier->verified($finding);

        return $finding;
    }

    /**
     * Perbaikan belum sesuai: kembali ke PIC dengan catatan. Deadline tidak berubah,
     * jadi jika sudah lewat statusnya langsung terlambat.
     */
    public function requestRevision(Finding $finding, string $note): Finding
    {
        $this->ensureStatus($finding, [FindingStatus::MenungguVerifikasi]);

        $finding->update([
            'status' => $finding->deadline?->lt(today())
                ? FindingStatus::Terlambat
                : FindingStatus::BelumSelesai,
            'revision_note' => $note,
        ]);

        $this->notifier->revisionRequested($finding);

        return $finding;
    }

    /**
     * Job harian: tandai temuan yang melewati deadline dan kirim eskalasi.
     *
     * @return Collection<int, Finding> temuan yang baru saja jadi terlambat
     */
    public function markOverdue(): Collection
    {
        $overdue = Finding::with(['pic', 'area'])
            ->where('status', FindingStatus::BelumSelesai)
            ->whereDate('deadline', '<', today())
            ->get();

        foreach ($overdue as $finding) {
            $finding->update(['status' => FindingStatus::Terlambat]);
            $this->notifier->overdue($finding);
        }

        return $overdue;
    }

    /**
     * Job harian: ingatkan PIC yang deadline-nya besok.
     *
     * @return Collection<int, Finding>
     */
    public function remindDueTomorrow(): Collection
    {
        $due = Finding::with(['pic', 'area'])
            ->where('status', FindingStatus::BelumSelesai)
            ->whereDate('deadline', today()->addDay())
            ->get();

        $due->each(fn (Finding $finding) => $this->notifier->dueTomorrow($finding));

        return $due;
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
