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
