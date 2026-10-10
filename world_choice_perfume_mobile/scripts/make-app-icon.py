#!/usr/bin/env python3
"""Generate every app-icon asset Expo needs from ONE square source image.

    python scripts/make-app-icon.py assets/images/app-icon-source.png
    python scripts/make-app-icon.py candidate.png --dry-run     # measure only
    python scripts/make-app-icon.py candidate.png --out /tmp/x # write elsewhere

The launcher icon on a customer's home screen is baked into the native build
(it is NOT part of an over-the-air update), so these files are the source of
truth for the next `eas build`.

Outputs (into assets/images/, the paths app.json points at):

    icon.png                     1024x1024 RGB   — iOS + Android launcher icon
                                                    (opaque: Apple rejects alpha)
    android-icon-foreground.png  1024x1024 RGBA  — adaptive icon mark, transparent,
                                                    scaled to Android's safe zone
    android-icon-background.png  1024x1024 RGB   — the artwork's own background
    android-icon-monochrome.png  1024x1024 RGBA  — white silhouette for themed icons
    favicon.png                  48x48           — web tab icon (the same artwork)

The mark is measured, not guessed: the background colour is read from the
corners, the artwork's bounding box is found, and the mark is scaled so it fills
ICON_FILL of the icon (leaving a clean margin) and SAFE_ZONE_FILL of the adaptive
canvas, whose outer ring Android crops away.
"""
from __future__ import annotations

import sys
from pathlib import Path

from PIL import Image, ImageChops

CANVAS = 1024
ICON_FILL = 0.78  # how much of the launcher icon the mark spans
SAFE_ZONE_FILL = 0.62  # adaptive foreground: mark inside Android's safe zone
FAVICON = 48
TOLERANCE = 18  # per-channel distance that still counts as "background"

HERE = Path(__file__).resolve().parent
ASSETS = HERE.parent / "assets" / "images"


def background_colour(image: Image.Image) -> tuple[int, int, int]:
    """The artwork's background, taken as the most common corner colour."""
    width, height = image.size
    corners = [
        image.getpixel((0, 0)),
        image.getpixel((width - 1, 0)),
        image.getpixel((0, height - 1)),
        image.getpixel((width - 1, height - 1)),
    ]
    corners = [tuple(pixel[:3]) for pixel in corners]
    return max(set(corners), key=corners.count)


def artwork_mask(image: Image.Image, background: tuple[int, int, int]) -> Image.Image:
    """White where the artwork is, black where the background shows through.

    A pixel counts as background only when EVERY channel is within tolerance, so
    a dark-but-coloured edge is never mistaken for flat background.
    """
    diff = ImageChops.difference(
        image.convert("RGB"), Image.new("RGB", image.size, background)
    )
    red, green, blue = diff.split()
    difference = ImageChops.lighter(ImageChops.lighter(red, green), blue)
    return difference.point(lambda value: 255 if value > TOLERANCE else 0)


def content_box(image: Image.Image, background: tuple[int, int, int]) -> tuple[int, int, int, int]:
    """Bounding box of everything that is not the background colour."""
    box = artwork_mask(image, background).getbbox()
    if box is None:
        raise SystemExit("no artwork found: every pixel matches the background colour")
    return box


def fit(mark: Image.Image, span: int) -> Image.Image:
    """Scale the mark so its longest side is `span`, keeping its proportions."""
    scale = span / max(mark.size)
    size = (max(1, round(mark.width * scale)), max(1, round(mark.height * scale)))
    return mark.resize(size, Image.LANCZOS)


def centred(canvas: Image.Image, mark: Image.Image) -> Image.Image:
    out = canvas.copy()
    out.paste(
        mark,
        ((canvas.width - mark.width) // 2, (canvas.height - mark.height) // 2),
        mark if mark.mode == "RGBA" else None,
    )
    return out


def transparent_background(mark: Image.Image, background: tuple[int, int, int]) -> Image.Image:
    """Turn the flat background into alpha, so the mark can sit on any surface."""
    mark = mark.convert("RGBA")
    mark.putalpha(artwork_mask(mark, background))
    return mark


def silhouette(mark: Image.Image) -> Image.Image:
    """White shape carrying the mark's alpha — what Android tints for themed icons."""
    shape = Image.new("RGBA", mark.size, (255, 255, 255, 0))
    shape.putalpha(mark.getchannel("A"))
    return shape


def main() -> int:
    args = sys.argv[1:]
    dry_run = "--dry-run" in args
    args = [argument for argument in args if argument != "--dry-run"]

    # --out DIR writes the assets somewhere else, so the pipeline can be tried
    # on a candidate artwork without touching the app's real icons.
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

    artwork = Image.open(source).convert("RGBA")
    if artwork.width != artwork.height:
        side = min(artwork.size)
        left = (artwork.width - side) // 2
        top = (artwork.height - side) // 2
        artwork = artwork.crop((left, top, left + side, top + side))
        print(f"cropped the source to a square {side}x{side}")

    background = background_colour(artwork)
    box = content_box(artwork, background)
    mark = artwork.crop(box)
    hex_background = "#%02X%02X%02X" % background

    print(f"source        {source} {artwork.width}x{artwork.height}")
    print(f"background    {hex_background}")
    print(f"artwork box   {box} ({mark.width}x{mark.height}, "
          f"{mark.width / artwork.width:.0%} of the canvas wide)")

    # Launcher icon: the mark on its own background, edge to edge and OPAQUE.
    icon_mark = fit(artwork.crop(box), round(CANVAS * ICON_FILL))
    icon = centred(Image.new("RGB", (CANVAS, CANVAS), background), icon_mark).convert("RGB")

    # Adaptive icon: transparent mark on a solid background, inside the safe zone.
    adaptive_mark = fit(transparent_background(mark, background), round(CANVAS * SAFE_ZONE_FILL))
    foreground = centred(Image.new("RGBA", (CANVAS, CANVAS), (0, 0, 0, 0)), adaptive_mark)
    background_image = Image.new("RGB", (CANVAS, CANVAS), background)
    monochrome = centred(
        Image.new("RGBA", (CANVAS, CANVAS), (255, 255, 255, 0)), silhouette(adaptive_mark)
    )
    # Web tab icon: the same launcher icon, small.
    favicon = icon.resize((FAVICON, FAVICON), Image.LANCZOS)

    if dry_run:
        print("dry run — nothing written, no app.json change needed")
        return 0

    output.mkdir(parents=True, exist_ok=True)

    icon.save(output / "icon.png", optimize=True)
    foreground.save(output / "android-icon-foreground.png", optimize=True)
    background_image.save(output / "android-icon-background.png", optimize=True)
    monochrome.save(output / "android-icon-monochrome.png", optimize=True)
    favicon.save(output / "favicon.png", optimize=True)

    for name in (
        "icon.png",
        "android-icon-foreground.png",
        "android-icon-background.png",
        "android-icon-monochrome.png",
        "favicon.png",
    ):
        written = Image.open(output / name)
        print(f"wrote         {name:30s} {written.width}x{written.height} {written.mode}")

    print(f"\nSet android.adaptiveIcon.backgroundColor to {hex_background} in app.json,")
    print("then rebuild (eas build) — a launcher icon never ships over the air.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
