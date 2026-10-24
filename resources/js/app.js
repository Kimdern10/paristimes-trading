/*
 * Admin scripts.
 *
 * imageUpload: phone photos are often 3-8 MB, larger than many hosts accept
 * (PHP's upload limit is often 2 MB). This shrinks a picture in the browser
 * to at most 2000px as a JPEG before Livewire uploads it, shows progress,
 * and shows a clear message if the upload still fails.
 */

const SHRINK_ABOVE_BYTES = 1024 * 1024;

/**
 * Resize an image file to fit within maxSide pixels and return it as a JPEG file.
 */
function shrinkImage(file, maxSide) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        const url = URL.createObjectURL(file);

        image.onload = () => {
            const scale = Math.min(1, maxSide / Math.max(image.naturalWidth, image.naturalHeight));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(image.naturalWidth * scale);
            canvas.height = Math.round(image.naturalHeight * scale);

            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);

            canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        reject(new Error('Could not shrink the image.'));

                        return;
                    }

                    const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg' }));
                },
                'image/jpeg',
                0.85,
            );
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Could not read the image.'));
        };

        image.src = url;
    });
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('imageUpload', (model, maxSide = 2000) => ({
        uploading: false,
        progress: 0,
        failed: false,

        init() {
            this.$wire.$watch(model, (value) => {
                if (!value) {
                    this.$refs.input.value = '';
                }
            });
        },

        async pick(event) {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            this.failed = false;
            this.uploading = true;
            this.progress = 0;

            let upload = file;

            if (file.type.startsWith('image/') && file.type !== 'image/gif' && file.size > SHRINK_ABOVE_BYTES) {
                try {
                    upload = await shrinkImage(file, maxSide);
                } catch {
                    upload = file;
                }
            }

            this.$wire.upload(
                model,
                upload,
                () => {
                    this.uploading = false;
                },
                () => {
                    this.uploading = false;
                    this.failed = true;
                },
                (progressEvent) => {
                    this.progress = progressEvent.detail.progress;
                },
            );
        },
    }));
});
