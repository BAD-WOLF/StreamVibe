// Helper functions for videos (preview, preload etc)

/**
 * Preloads a video to improve the hover experience
 */
export function preloadVideo(src: string): HTMLVideoElement {
    const video: HTMLVideoElement = document.createElement("video");
    video.src = src;
    video.preload = "auto";
    return video;
}
