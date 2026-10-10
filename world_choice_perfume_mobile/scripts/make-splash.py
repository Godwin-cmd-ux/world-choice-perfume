#!/usr/bin/env python3
"""Turn the splash artwork into the app's splash asset.

    python scripts/make-splash.py assets/images/app-splash-source.jpg

The supplied artwork is a dark panel with the logo (mark + wordmark) in the
middle. A splash must not bake that panel in: on Android 12+ the system draws
the splash icon itself, and iOS/older Android scale it to fit — so the panel is
trimmed to the logo and its flat background becomes transparent, letting
app.json's splash `backgroundColor` fill the screen on every device. That colour
is printed below and must match this artwork's background, which is read from
the panel's corners.

Same code path as scripts/make-app-icon.py, one asset instead of five.
"""
from __future__ import annotations

import sys
from pathlib import Path

from PIL import Image, ImageChops

BACKGROUND_TOLERANCE = 18  # a channel distance this small is still "background"
SOFT_EDGE = 46  # …up to here the alpha ramps, which kills JPEG ringing halos
PADDING = 0.06  # breathing room around the logo, as a share of its size

HERE = Path(__file__).resolve().parent
ASSETS = HERE.parent / "assets" / "images"


def main() -> int:
    args = sys.argv[1:]
    output = ASSETS
    if "--out" in args:
        index = args.index("--out")
        if index + 1 >= len(args):
            raise SystemExit("--out needs a directory")
        output = Path(args[index + 1])
        del args[index : index + 2]

    if len(args) != 1:
        print(__doc__)
        return 2

    source = Path(args[0])
    if not source.is_file():
        raise SystemExit(f"source image not found: {source}")

    artwork = Image.open(source).convert("RGB")
    width, height = artwork.size

    # The panel colour, averaged over its border so JPEG noise on one corner
    # cannot pick the wrong shade for the screen behind the logo.
    border = [
        *[artwork.getpixel((x, 0)) for x in range(0, width, 3)],
        *[artwork.getpixel((x, height - 1)) for x in range(0, width, 3)],
        *[artwork.getpixel((0, y)) for y in range(0, height, 3)],
        *[artwork.getpixel((width - 1, y)) for y in range(0, height, 3)],
    ]
    background = tuple(
        round(sum(pixel[channel] for pixel in border) / len(border)) for channel in range(3)
    )
    hex_background = "#%02X%02X%02X" % background

    # How far every pixel sits from the panel colour, per channel.
    difference = ImageChops.difference(artwork, Image.new("RGB", artwork.size, background))
    red, green, blue = difference.split()
    distance = ImageChops.lighter(ImageChops.lighter(red, green), blue)

    solid = distance.point(lambda value: 255 if value > BACKGROUND_TOLERANCE else 0)
    box = solid.getbbox()
    if box is None:
        raise SystemExit("no logo found: every pixel matches the panel colour")

    pad_x = round((box[2] - box[0]) * PADDING)
    pad_y = round((box[3] - box[1]) * PADDING)
    box = (
        max(0, box[0] - pad_x),
        max(0, box[1] - pad_y),
        min(width, box[2] + pad_x),
        min(height, box[3] + pad_y),
    )

    logo = artwork.crop(box)
    alpha = distance.crop(box).point(
        # Fully clear at the panel colour, fully solid once clearly off it, and a
        # smooth ramp between — so anti-aliased edges keep no dark halo.
        lambda value: 0 if value <= BACKGROUND_TOLERANCE
        else (255 if value >= SOFT_EDGE else round((value - BACKGROUND_TOLERANCE) * 255 / (SOFT_EDGE - BACKGROUND_TOLERANCE)))
    )
    logo = logo.convert("RGBA")
    logo.putalpha(alpha)

    output.mkdir(parents=True, exist_ok=True)
    logo.save(output / "splash-icon.png", optimize=True)

    written = Image.open(output / "splash-icon.png")
    print(f"source        {source} ({width}x{height})")
    print(f"panel colour  {hex_background}")
    print(f"logo box      {box} ({logo.width}x{logo.height})")
    print(f"wrote         splash-icon.png {written.width}x{written.height} {written.mode}")
    print(f"\nSet the splash backgroundColor in app.json to {hex_background}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
