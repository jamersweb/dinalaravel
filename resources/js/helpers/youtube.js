/**
 * Extract 11-character YouTube video id from watch, embed, youtu.be, or /shorts/ URLs.
 */
export function extractYoutubeVideoId(raw) {
    const input = String(raw || '').trim();
    if (!input) {
        return null;
    }
    const patterns = [
        /(?:https?:\/\/)?(?:www\.)?youtube\.com\/shorts\/([A-Za-z0-9_-]{11})/i,
        /(?:https?:\/\/)?youtu\.be\/([A-Za-z0-9_-]{11})/i,
        /\/embed\/([A-Za-z0-9_-]{11})(?:\?|#|$)/i,
        /[?&]v=([A-Za-z0-9_-]{11})(?:[&=#?]|$)/i,
    ];
    for (let i = 0; i < patterns.length; i++) {
        const m = input.match(patterns[i]);
        if (m) {
            return m[1];
        }
    }
    const bare = input.match(/^([A-Za-z0-9_-]{11})$/);
    return bare ? bare[1] : null;
}
