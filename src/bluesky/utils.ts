export const sanitizeUrl = (url: string) => {
    try {
        const sanitizedUrl = new URL(url);
        if (sanitizedUrl.protocol === "http:" || sanitizedUrl.protocol === "https:") {
            return sanitizedUrl.href;
        }
    } catch {
        try {
            const sanitizedUrl = new URL("https://" + url);
            return sanitizedUrl.href;
        } catch {
            return "";
        }
    }
    return "";
}