import { Calendar } from '@fullcalendar/core';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import idLocale from '@fullcalendar/core/locales/id';

/**
 * Kalender ketersediaan ruangan (halaman Booking Ruangan).
 * Data booking diambil dari Livewire ($wire.events), pilihan slot dikirim ke $wire.pickSlot.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('roomCalendar', (openHour, closeHour) => ({
        calendar: null,

        init() {
            const mobile = window.matchMedia('(max-width: 768px)').matches;

            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [timeGridPlugin, interactionPlugin],
                locale: idLocale,
                initialView: mobile ? 'timeGridDay' : 'timeGridWeek',
                headerToolbar: { left: 'prev,next today', center: 'title', right: mobile ? '' : 'timeGridWeek,timeGridDay' },
                slotMinTime: `${String(openHour).padStart(2, '0')}:00:00`,
                slotMaxTime: `${String(closeHour).padStart(2, '0')}:00:00`,
                slotDuration: '00:30:00',
                allDaySlot: false,
                nowIndicator: true,
                height: 'auto',
                selectable: true,
                selectMirror: true,
                selectOverlap: false,
                longPressDelay: 300,
                selectAllow: (info) => info.start >= new Date(Date.now() - 5 * 60 * 1000) && info.start.toDateString() === new Date(info.end - 1).toDateString(),
                events: (info, success, failure) => {
                    this.$wire.events(info.startStr, info.endStr).then(success).catch(failure);
                },
                select: (info) => {
                    this.$wire.pickSlot(info.startStr, info.endStr);
                    this.calendar.unselect();
                },
            });

            this.calendar.render();
        },

        refresh() {
            this.calendar?.refetchEvents();
        },

        destroy() {
            this.calendar?.destroy();
        },
    }));
});

/**
 * Perkecil foto dari kamera HP sebelum diunggah (hemat kuota & penyimpanan).
 * Mengembalikan File JPEG dengan sisi terpanjang maksimal `maxSize` piksel.
 * Jika gagal (format tidak didukung browser), file asli dikembalikan.
 */
window.compressImage = async function (file, maxSize = 1600, quality = 0.8) {
    if (!file || !file.type.startsWith('image/')) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, maxSize / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
        if (!blob || blob.size >= file.size) {
            return file;
        }

        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], name, { type: 'image/jpeg' });
    } catch (e) {
        return file;
    }
};
