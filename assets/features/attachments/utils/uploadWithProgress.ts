export function uploadWithProgress(
  url: string,
  file: File,
  signal: AbortSignal,
  onProgress: (loaded: number) => void,
): Promise<void> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('PUT', url);
    xhr.setRequestHeader('Content-Type', file.type || 'application/octet-stream');

    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) onProgress(e.loaded);
    };
    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        onProgress(file.size);
        resolve();
      } else {
        reject(new Error(`Failed to upload ${file.name}`));
      }
    };
    xhr.onerror = () => reject(new Error(`Failed to upload ${file.name}`));
    xhr.onabort = () => reject(new DOMException('Aborted', 'AbortError'));

    if (signal.aborted) {
      xhr.abort();
      return;
    }
    signal.addEventListener('abort', () => xhr.abort());

    xhr.send(file);
  });
}
