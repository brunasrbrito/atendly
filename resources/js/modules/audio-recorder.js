/**
 * Grava um áudio pelo microfone e o adiciona como anexo da interação.
 * Botão: <button data-record-audio="#id-do-input-file">
 */
export function initAudioRecorder() {
    document.querySelectorAll('[data-record-audio]').forEach((button) => {
        const input = document.querySelector(button.dataset.recordAudio);
        if (!input || !navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
            button.hidden = true;
            return;
        }

        let recorder = null;
        let chunks = [];
        const label = button.querySelector('[data-record-label]');

        button.addEventListener('click', async () => {
            if (recorder?.state === 'recording') {
                recorder.stop();
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                recorder = new MediaRecorder(stream);
                chunks = [];

                recorder.ondataavailable = (e) => chunks.push(e.data);
                recorder.onstop = () => {
                    stream.getTracks().forEach((t) => t.stop());
                    const type = recorder.mimeType || 'audio/webm';
                    const ext = type.includes('ogg') ? 'ogg' : 'webm';
                    const file = new File(chunks, `audio-${Date.now()}.${ext}`, { type });
                    appendFile(input, file);
                    button.classList.remove('is-recording');
                    label.textContent = 'Gravar áudio';
                };

                recorder.start();
                button.classList.add('is-recording');
                label.textContent = 'Parar gravação';
            } catch {
                alert('Não foi possível acessar o microfone.');
            }
        });
    });
}

function appendFile(input, file) {
    const transfer = new DataTransfer();
    [...input.files].forEach((f) => transfer.items.add(f));
    transfer.items.add(file);
    input.files = transfer.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
}
