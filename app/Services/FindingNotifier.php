<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Finding;
use App\Models\User;
use App\Notifications\FindingNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Menentukan siapa menerima notifikasi untuk setiap kejadian temuan (PRD MVP §4.2, §4.5).
 */
class FindingNotifier
{
    public function reported(Finding $finding): void
    {
        $this->send($this->roles(Role::Koordinator5R, Role::Admin), $finding, 'reported',
            'Laporan temuan baru',
            "{$finding->reporter->name} melaporkan temuan baru. Silakan tunjuk PIC.",
            $this->panelUrl($finding));
    }

    public function assigned(Finding $finding): void
    {
        $this->send(collect([$finding->pic]), $finding, 'assigned',
            'Anda ditunjuk sebagai PIC',
            'Anda ditunjuk menindaklanjuti temuan ini. Unggah foto sesudah perbaikan sebelum deadline.',
            $this->picUrl($finding));
    }

    public function rejected(Finding $finding): void
    {
        $this->send(collect([$finding->reporter]), $finding, 'rejected',
            'Laporan Anda ditolak',
            "Alasan: {$finding->rejection_reason}",
            $this->picUrl($finding));
    }

    public function submitted(Finding $finding): void
    {
        $this->send($this->roles(Role::Koordinator5R, Role::Admin), $finding, 'submitted',
            'Tindak lanjut menunggu verifikasi',
            "{$finding->pic->name} sudah mengirim foto perbaikan. Silakan verifikasi.",
            $this->panelUrl($finding));
    }

    public function verified(Finding $finding): void
    {
        $this->send(collect([$finding->pic, $finding->reporter]), $finding, 'verified',
            'Temuan selesai',
            'Perbaikan sudah diverifikasi dan temuan dinyatakan selesai. Terima kasih!',
            $this->picUrl($finding));
    }

    public function revisionRequested(Finding $finding): void
    {
        $this->send(collect([$finding->pic]), $finding, 'revision',
            'Tindak lanjut perlu revisi',
            "Catatan revisi: {$finding->revision_note}",
            $this->picUrl($finding));
    }

    public function dueTomorrow(Finding $finding): void
    {
        $this->send(collect([$finding->pic]), $finding, 'due_tomorrow',
            'Pengingat: deadline besok',
            'Temuan ini harus ditindaklanjuti paling lambat besok.',
            $this->picUrl($finding));
    }

    public function overdue(Finding $finding): void
    {
        $this->send(collect([$finding->pic]), $finding, 'overdue',
            'Temuan Anda terlambat',
            'Deadline temuan ini sudah lewat. Segera unggah foto perbaikan.',
            $this->picUrl($finding));

        $this->send($this->roles(Role::Management, Role::Koordinator5R), $finding, 'overdue',
            'Eskalasi: temuan terlambat',
            "Temuan dengan PIC {$finding->pic?->name} sudah melewati deadline.",
            $this->panelUrl($finding));
    }

    /**
     * @param  Collection<int, User|null>  $users
     */
    private function send(Collection $users, Finding $finding, string $event, string $subject, string $message, string $url): void
    {
        $recipients = $users->filter(fn (?User $user) => $user?->is_active)->unique('id')->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new FindingNotification($finding, $event, $subject, $message, $url));
    }

    /**
     * @return Collection<int, User>
     */
    private function roles(Role ...$roles): Collection
    {
        return User::whereIn('role', $roles)->where('is_active', true)->get();
    }

    private function picUrl(Finding $finding): string
    {
        return route('findings.show', $finding);
    }

    private function panelUrl(Finding $finding): string
    {
        return route('filament.admin.resources.findings.view', $finding);
    }
}
