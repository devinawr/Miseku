<?php

namespace App\Services;

use App\Models\CommunityReport;
use App\Models\DiscussionTopic;
use App\Models\FileUpload;
use App\Models\TopicMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Moderasi komunitas:
 *  - pengguna melaporkan topik / pesan  → antrean di Panel admin → Komunitas
 *  - admin menghapus konten dengan alasan → penulis & pelapor dapat notifikasi
 *  - penulis menghapus kontennya sendiri  → tanpa notifikasi
 * Setiap penghapusan ikut menghapus foto lampiran (file di public/uploads + baris file_upload).
 */
class CommunityModerationService
{
    public function __construct(private NotificationService $notify) {}

    /* ------------------------------------------------------------ laporan */

    public function report(User $reporter, DiscussionTopic $topic, ?TopicMessage $message, string $category, ?string $note): CommunityReport
    {
        $authorId = $message ? $message->user_id : $topic->creator_id;
        abort_if($authorId === $reporter->id, 422, 'Tidak bisa melaporkan konten sendiri.');

        $report = CommunityReport::create([
            'reporter_id'         => $reporter->id,
            'reported_user_id'    => $authorId,
            'discussion_topic_id' => $topic->id,
            'topic_message_id'    => $message?->id,
            'reason_category'     => $category,
            'reason_note'         => $note ? mb_substr(trim($note), 0, 255) : null,
            'reported_content'    => $this->snapshot($topic, $message),
            'report_status'       => 'pending',
        ]);

        $this->notify->toAdmins('community', 'Laporan komunitas baru',
            "{$reporter->name} melaporkan " . ($message ? 'pesan' : 'topik') . " di \"{$topic->title}\" ({$report->reason_label}). Cek di Panel admin → Komunitas.");

        return $report;
    }

    /** Sudah pernah melapor konten ini dan masih menunggu? (cegah laporan dobel) */
    public function alreadyReported(User $reporter, DiscussionTopic $topic, ?TopicMessage $message): bool
    {
        return CommunityReport::where('reporter_id', $reporter->id)
            ->where('discussion_topic_id', $topic->id)
            ->where('topic_message_id', $message?->id)
            ->where('report_status', 'pending')
            ->exists();
    }

    public function dismiss(CommunityReport $report, User $admin, ?string $note): void
    {
        $report->update([
            'report_status' => 'dismissed',
            'handled_by'    => $admin->id,
            'admin_note'    => $note ? mb_substr(trim($note), 0, 255) : null,
            'handled_at'    => now(),
        ]);

        $this->notify->send($report->reporter_id, 'community', 'Laporan komunitas ditinjau',
            'Laporanmu sudah ditinjau admin. Konten tersebut dinilai tidak melanggar aturan komunitas'
            . ($report->admin_note ? ": {$report->admin_note}" : '.'), null, false);
    }

    /* ------------------------------------------------------------ hapus */

    /**
     * Hapus pesan (beserta balasannya). $admin + $reason diisi bila dihapus admin.
     */
    public function deleteMessage(TopicMessage $message, ?User $admin = null, ?string $reason = null): void
    {
        $message->loadMissing('topic');
        $topic = $message->topic;

        DB::transaction(function () use ($message, $admin, $reason, $topic) {
            $ids = TopicMessage::where('parent_message_id', $message->id)->pluck('id')->push($message->id);

            if ($admin) {
                $this->closeReports(fn ($q) => $q->whereIn('topic_message_id', $ids), $admin, $reason);
                $this->notify->send($message->user_id, 'community', 'Pesanmu dihapus admin',
                    "Pesanmu di topik \"{$topic->title}\" dihapus admin karena: {$reason}\n\nIsi pesan: \"" . Str::limit($message->message_body, 120) . '"');
            }

            $this->deletePhotos($ids);
            TopicMessage::whereIn('id', $ids)->where('id', '!=', $message->id)->delete();
            $message->delete();
        });
    }

    /** Hapus topik beserta semua pesannya. */
    public function deleteTopic(DiscussionTopic $topic, ?User $admin = null, ?string $reason = null): void
    {
        DB::transaction(function () use ($topic, $admin, $reason) {
            $ids = TopicMessage::where('discussion_topic_id', $topic->id)->pluck('id');

            if ($admin) {
                $this->closeReports(fn ($q) => $q->where('discussion_topic_id', $topic->id), $admin, $reason);
                $this->notify->send($topic->creator_id, 'community', 'Topikmu dihapus admin',
                    "Topik \"{$topic->title}\" beserta semua balasannya dihapus admin karena: {$reason}");
            }

            $this->deletePhotos($ids);
            TopicMessage::where('discussion_topic_id', $topic->id)->whereNotNull('parent_message_id')->delete();
            TopicMessage::where('discussion_topic_id', $topic->id)->delete();
            $topic->delete();
        });
    }

    /* ------------------------------------------------------------ bantuan */

    /** Laporan pending untuk konten yang dihapus → removed + kabari pelapor. */
    private function closeReports(\Closure $scope, User $admin, ?string $reason): void
    {
        CommunityReport::where('report_status', 'pending')->where($scope)->get()
            ->each(function (CommunityReport $r) use ($admin, $reason) {
                $r->update([
                    'report_status' => 'removed',
                    'handled_by'    => $admin->id,
                    'admin_note'    => $reason ? mb_substr($reason, 0, 255) : null,
                    'handled_at'    => now(),
                ]);
                $this->notify->send($r->reporter_id, 'community', 'Laporanmu ditindaklanjuti',
                    'Terima kasih sudah melapor. Konten yang kamu laporkan sudah dihapus admin.', null, false);
            });
    }

    /** Hapus file foto di public/uploads + barisnya di file_upload. */
    private function deletePhotos($messageIds): void
    {
        $files = FileUpload::where('source_table', 'topic_message')->whereIn('source_id', $messageIds)->get();

        foreach ($files as $f) {
            $path = public_path(ltrim($f->url, '/'));
            // hanya file di dalam public/uploads yang boleh dihapus
            if (str_starts_with($f->url, '/uploads/') && is_file($path)) {
                @unlink($path);
            }
        }

        FileUpload::whereIn('id', $files->pluck('id'))->delete();
    }

    private function snapshot(DiscussionTopic $topic, ?TopicMessage $message): string
    {
        if ($message) {
            $photos = $message->images()->count();

            return trim($message->message_body) . ($photos ? "\n[+ {$photos} foto]" : '');
        }

        $first = TopicMessage::where('discussion_topic_id', $topic->id)->whereNull('parent_message_id')->orderBy('sent_at')->first();

        return "Judul: {$topic->title}" . ($first ? "\n" . trim($first->message_body) : '');
    }
}
