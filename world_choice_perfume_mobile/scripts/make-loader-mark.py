#!/usr/bin/env python3
"""Turn the brand artwork into the mark the Golden W loader animates.

    python scripts/make-loader-mark.py                          # both surfaces
    python scripts/make-loader-mark.py --size 512 --dry-run     # measure only

The loader shows the REAL World Choice Perfume mark — the gold-and-white W from
assets/images/app-icon-source.png. No vector W exists in the repository (the
website only has JPEG logos), so rather than inventing a generic monogram the
loader reuses the brand artwork with its flat black backdrop keyed out, so it
sits on any page background without a plate behind it.

Two copies are written, one per surface:

    world_choice_perfume_mobile/assets/images/golden-w-mark.png   (384x384)
    public/images/golden-w-mark.png                               (512x512)

Both are the same square canvas: the mark centred, spanning MARK_FILL of the
width, fully transparent elsewhere. The website's SVG wrapper uses
viewBox="0 0 512 512", which is why the square canvas matters.

Edge quality: keying a mark off a black backdrop leaves anti-aliased boundary
pixels that are dark but semi-transparent. Left alone they read as a grey halo
on the site's ivory background, so every partial-alpha pixel takes the colour of
the opaque pixels next to it (spread with a small max-filter dilation) while
keeping its alpha. That is what makes the mark crisp on light AND dark pages.
"""
from __future__ import annotations

import sys
from pathlib import Path

from PIL import Image, ImageChops, ImageFilter

MARK_FILL = 0.86  # share of the canvas width the mark spans
TOLERANCE = 18  # per-channel distance that still counts as "background"
SOFT_EDGE = 48  # distance at which a pixel becomes fully opaque
MIN_ALPHA = 12  # below this a pixel is invisible anyway — drop the artwork's haze
SPREAD_KERNEL = 7  # dilation used to pull the mark's colour into its edge pixels
SPREAD_PASSES = 2
MOBILE_SIZE = 384
WEB_SIZE = 512

HERE = Path(__file__).resolve().parent
MOBILE_ASSETS = HERE.parent / "assets" / "images"
WEB_ASSETS = HERE.parent.parent / "public" / "images"
SOURCE = MOBILE_ASSETS / "app-icon-source.png"


def background_colour(image: Image.Image) -> tuple[int, int, int]:
    """The artwork's backdrop, taken as the most common corner colour."""
    width, height = image.size
    corners = [
        tuple(image.getpixel((0, 0))[:3]),
        tuple(image.getpixel((width - 1, 0))[:3]),
        tuple(image.getpixel((0, height - 1))[:3]),
        tuple(image.getpixel((width - 1, height - 1))[:3]),
    ]
    return max(set(corners), key=corners.count)


def backdrop_distance(image: Image.Image, background: tuple[int, int, int]) -> Image.Image:
    """Per-pixel distance from the backdrop, as an 8-bit greyscale image."""
    diff = ImageChops.difference(image.convert("RGB"), Image.new("RGB", image.size, background))
    red, green, blue = diff.split()
    return ImageChops.lighter(ImageChops.lighter(red, green), blue)


def spread_colour(image: Image.Image, alpha: Image.Image) -> Image.Image:
    """Give every semi-transparent pixel the colour of the opaque pixels near it.

    A keyed edge pixel still carries the backdrop's colour, so on a light page it
    would show as a dark fringe. Dilating the opaque colours over the edge ring
    removes that fringe without touching the alpha channel — the mark's outline
    stays exactly as drawn.

    Two 7x7 passes reach ~7px, which (measured) leaves only a handful of dark
    pixels above alpha 90 against the artwork's ~750px wide mark.
    """
    opaque = alpha.point(lambda value: 255 if value >= 250 else 0)
    spread = []
    for channel in image.convert("RGB").split():
        kept = Image.composite(channel, Image.new("L", image.size, 0), opaque)
        for _ in range(SPREAD_PASSES):
            kept = kept.filter(ImageFilter.MaxFilter(SPREAD_KERNEL))
        spread.append(kept)
    return Image.merge("RGB", spread)


def main() -> int:
    args = sys.argv[1:]
    dry_run = "--dry-run" in args
    args = [argument for argument in args if argument != "--dry-run"]

    size = MOBILE_SIZE
    if "--size" in args:
        index = args.index("--size")
        if index + 1 >= len(args):
            raise SystemExit("--size needs a number")
        size = int(args[index + 1])
        del args[index : index + 2]

    source = Path(args[0]) if args else SOURCE
    if not source.is_file():
        raise SystemExit(f"source artwork not found: {source}")

    artwork = Image.open(source).convert("RGBA")
    if artwork.width != artwork.height:
        side = min(artwork.size)
        artwork = artwork.crop(
            (
                (artwork.width - side) // 2,
                (artwork.height - side) // 2,
                (artwork.width - side) // 2 + side,
                (artwork.height - side) // 2 + side,
            )
        )

    background = background_colour(artwork)
    distance = backdrop_distance(artwork, background)
    box = distance.point(lambda value: 255 if value > TOLERANCE else 0).getbbox()
    if box is None:
        raise SystemExit("no mark found: every pixel matches the backdrop colour")

    mark = artwork.crop(box).convert("RGBA")
    alpha = distance.crop(
        box
    ).point(
        lambda value: 0
        if value <= TOLERANCE
        else (
            0
            if round((value - TOLERANCE) * 255 / (SOFT_EDGE - TOLERANCE)) < MIN_ALPHA
            else min(255, round((value - TOLERANCE) * 255 / (SOFT_EDGE - TOLERANCE)))
        )
    )
    mark.putalpha(alpha)
    mark = Image.merge("RGB", spread_colour(mark, alpha).split()).convert("RGBA")
    mark.putalpha(alpha)

    if dry_run:
        print(f"source        {source} {artwork.width}x{artwork.height}")
        print(f"backdrop      #%02X%02X%02X" % background)
        print(f"mark box      {box} ({mark.width}x{mark.height})")
        print("dry run — nothing written")
        return 0

    outputs = [
        (MOBILE_ASSETS / "golden-w-mark.png", MOBILE_SIZE),
        (WEB_ASSETS / "golden-w-mark.png", WEB_SIZE),
    ]

    for target, canvas in outputs:
        span = round(canvas * MARK_FILL)
        scale = span / max(mark.size)
        scaled = mark.resize(
            (max(1, round(mark.width * scale)), max(1, round(mark.height * scale))),
            Image.LANCZOS,
        )
        sheet = Image.new("RGBA", (canvas, canvas), (0, 0, 0, 0))
        sheet.paste(
            scaled,
            ((canvas - scaled.width) // 2, (canvas - scaled.height) // 2),
            scaled,
        )
        target.parent.mkdir(parents=True, exist_ok=True)
        sheet.save(target, optimize=True)

        written = Image.open(target)
        corners = [written.getpixel(p)[3] for p in ((0, 0), (canvas - 1, 0), (0, canvas - 1))]
        content = written.getchannel("A").point(lambda value: 255 if value > 0 else 0).getbbox()
        print(f"wrote  {target}")
        print(
            f"       {written.width}x{written.height} {written.mode} "
            f"| corners alpha {corners} | mark spans "
            f"{round((content[2] - content[0]) / canvas * 100)}% wide, "
            f"{round((content[3] - content[1]) / canvas * 100)}% tall"
        )

    print(f"\nsource        {source} {artwork.width}x{artwork.height}")
    print(f"backdrop      #%02X%02X%02X (keyed to transparent)" % background)
    print(f"mark box      {box} ({mark.width}x{mark.height})")
    print(f"website viewBox for the 512 asset: 0 0 {WEB_SIZE} {WEB_SIZE}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
