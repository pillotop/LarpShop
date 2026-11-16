export function cropRectangle(sourceWidth, sourceHeight, width, height, zoom, horizontal, vertical) {
    const scale = Math.max(width / sourceWidth, height / sourceHeight) * zoom;
    const cropWidth = width / scale;
    const cropHeight = height / scale;

    return {
        x: (sourceWidth - cropWidth) * horizontal,
        y: (sourceHeight - cropHeight) * vertical,
        width: cropWidth,
        height: cropHeight,
    };
}
