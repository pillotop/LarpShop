import test from 'node:test';
import assert from 'node:assert/strict';
import { cropRectangle } from '../../resources/js/components/image_crop_geometry.js';

test('portrait images fill the wide frame without stretching', () => {
    assert.deepEqual(cropRectangle(400, 800, 1600, 400, 1, 0.5, 0.5), {
        x: 0, y: 350, width: 400, height: 100,
    });
});

test('zoom and positioning keep every crop within source bounds', () => {
    for (const [width, height] of [[400, 800], [3200, 400], [1600, 400]]) {
        for (const zoom of [1, 1.5, 3]) {
            for (const position of [0, 0.5, 1]) {
                const crop = cropRectangle(width, height, 1600, 400, zoom, position, position);
                assert.ok(crop.x >= 0 && crop.y >= 0);
                assert.ok(crop.x + crop.width <= width + 0.00001);
                assert.ok(crop.y + crop.height <= height + 0.00001);
                assert.equal(crop.width / crop.height, 4);
            }
        }
    }
});
