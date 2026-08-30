#!/usr/bin/env python3
"""Build a vertical 9:16 launch promo with real app footage + loud clear audio."""

from __future__ import annotations

import math
import os
import subprocess
import wave
from pathlib import Path

import cv2
import imageio_ffmpeg
import numpy as np
from PIL import Image, ImageDraw, ImageEnhance, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = Path(__file__).resolve().parent
ASSETS = OUT_DIR / "promo-assets"
SCREENS = ASSETS / "screens"
LOGO = ROOT / "public" / "images" / "brand" / "dateconta-logo-512.png"
OUT_MP4 = OUT_DIR / "DateConta-Facturare-Lansare-Promo.mp4"
DESKTOP_COPY = Path("/Users/razvanivan/Desktop/DateConta-Facturare-Lansare-Promo.mp4")

W, H = 1080, 1920
FPS = 30
TARGET_DUR = 44.0

TEAL = (10, 52, 64)
TEAL_MID = (15, 76, 92)
AMBER = (224, 138, 30)
AMBER_HOT = (255, 184, 77)
CREAM = (255, 248, 236)
WHITE = (255, 255, 255)

VOICE_TEXT = (
    "Hey! Ai văzut aplicația asta? Ia uite — DateConta Facturare! "
    "Serios, e făcută pentru firme din România: emiti facturi, proforme, avize, chitanțe, "
    "în câteva minute, fără bătăi de cap. "
    "Ai dashboard, clienți, încasări, PDF pe email… totul dintr-un singur loc. "
    "Și acum vine partea bună: e gratuit până la treizeci și unu martie două mii douăzeci și șapte! "
    "Lansarea oficială e pe cincisprezece august, la ora zece. "
    "Poți testa chiar acum. Intră pe factura punct dateconta punct ro și începe gratuit. Hai, că merită!"
)

# (start, end, kind, title, subtitle, screen_glob_or_none)
SCENE_PLAN = [
    (0.0, 5.0, "hook", "DateConta\nFacturare", "Facturi online pentru firme din România", "01-landing-hero.png"),
    (5.0, 12.5, "app", "Dashboard-ul tău", "Vezi facturat, încasat și restante", "50-dashboard.png"),
    (12.5, 19.5, "app", "Emite documente", "Facturi · Proforme · Avize · Chitanțe", "54-invoice-create.png"),
    (19.5, 26.0, "app", "Clienți & încasări", "Tot fluxul, într-un singur loc", "52-clients.png"),
    (26.0, 34.5, "launch", "LANSARE!!!", "15 august 2026 · ora 10:00", None),
    (34.5, 44.0, "cta", "Începe gratuit acum", "factura.dateconta.ro", "51-documents.png"),
]


def load_font(size: int, bold: bool = True) -> ImageFont.FreeTypeFont:
    candidates = (
        [
            "/System/Library/Fonts/Supplemental/Arial Black.ttf",
            "/System/Library/Fonts/Supplemental/Arial Bold.ttf",
            "/Library/Fonts/Arial Bold.ttf",
            "/System/Library/Fonts/Helvetica.ttc",
        ]
        if bold
        else [
            "/System/Library/Fonts/Supplemental/Arial.ttf",
            "/Library/Fonts/Arial.ttf",
            "/System/Library/Fonts/Helvetica.ttc",
        ]
    )
    for path in candidates:
        if os.path.exists(path):
            try:
                return ImageFont.truetype(path, size=size)
            except OSError:
                continue
    return ImageFont.load_default()


def probe_duration(path: Path) -> float:
    ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
    probe = subprocess.run([ffmpeg, "-i", str(path)], capture_output=True, text=True)
    for line in probe.stderr.splitlines():
        if "Duration:" in line:
            part = line.split("Duration:")[1].split(",")[0].strip()
            h, m, s = part.split(":")
            return int(h) * 3600 + int(m) * 60 + float(s)
    return 0.0


def ease_out(t: float) -> float:
    t = max(0.0, min(1.0, t))
    return 1 - (1 - t) ** 3


def draw_gradient(base: Image.Image, t: float) -> None:
    draw = ImageDraw.Draw(base)
    for y in range(H):
        p = y / (H - 1)
        warm = max(0.0, (p - 0.55) / 0.45)
        r = int(TEAL[0] * (1 - warm) + AMBER[0] * warm * 0.55)
        g = int(TEAL[1] * (1 - p * 0.2) + AMBER[1] * warm * 0.4)
        b = int(TEAL[2] * (1 - warm) + 40)
        draw.line([(0, y), (W, y)], fill=(r, g, b))


def fit_cover(img: Image.Image, tw: int, th: int, zoom: float = 1.0, pan_x: float = 0.5, pan_y: float = 0.5) -> Image.Image:
    img = img.convert("RGB")
    scale = max(tw / img.width, th / img.height) * zoom
    nw, nh = max(1, int(img.width * scale)), max(1, int(img.height * scale))
    img = img.resize((nw, nh), Image.Resampling.LANCZOS)
    max_x = max(0, nw - tw)
    max_y = max(0, nh - th)
    x = int(max_x * pan_x)
    y = int(max_y * pan_y)
    return img.crop((x, y, x + tw, y + th))


def phone_frame(screen: Image.Image, t: float, local: float) -> Image.Image:
    """Place desktop/app screenshot into a large rounded device frame."""
    canvas = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    zoom = 1.04 + 0.04 * local
    pan_x = 0.35 + 0.3 * (0.5 + 0.5 * math.sin(t * 0.7))
    pan_y = 0.25 + 0.2 * local
    content = fit_cover(screen, 920, 1280, zoom=zoom, pan_x=pan_x, pan_y=pan_y).convert("RGBA")

    frame_w, frame_h = 960, 1340
    frame = Image.new("RGBA", (frame_w, frame_h), (0, 0, 0, 0))
    fd = ImageDraw.Draw(frame)
    fd.rounded_rectangle([0, 0, frame_w - 1, frame_h - 1], radius=42, fill=(20, 28, 34, 255))
    fd.rounded_rectangle([16, 16, frame_w - 17, frame_h - 17], radius=32, fill=(255, 255, 255, 255))
    # content area
    inner = content.resize((frame_w - 40, frame_h - 40), Image.Resampling.LANCZOS)
    mask = Image.new("L", inner.size, 0)
    md = ImageDraw.Draw(mask)
    md.rounded_rectangle([0, 0, inner.size[0] - 1, inner.size[1] - 1], radius=26, fill=255)
    frame.paste(inner, (20, 20), mask)

    # shadow
    shadow = Image.new("RGBA", (frame_w + 60, frame_h + 60), (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow)
    sd.rounded_rectangle([20, 30, frame_w + 40, frame_h + 50], radius=48, fill=(0, 0, 0, 90))
    shadow = shadow.filter(ImageFilter.GaussianBlur(18))

    x = (W - frame_w) // 2
    y = 290 + int((1 - ease_out(min(1, local * 3))) * 40)
    canvas.alpha_composite(shadow, (x - 30, y - 20))
    canvas.alpha_composite(frame, (x, y))
    return canvas


def draw_star(draw: ImageDraw.ImageDraw, cx: float, cy: float, r: float, fill, outline=None, width=4):
    pts = []
    for i in range(10):
        ang = -math.pi / 2 + i * math.pi / 5
        rad = r if i % 2 == 0 else r * 0.42
        pts.append((cx + rad * math.cos(ang), cy + rad * math.sin(ang)))
    draw.polygon(pts, fill=fill, outline=outline)
    if outline and width:
        draw.line(pts + [pts[0]], fill=outline, width=width)


def center_text(draw, lines, font, y, fill, stroke_fill=None, stroke_width=0, line_gap=1.12):
    ascent, descent = font.getmetrics()
    line_h = int((ascent + descent) * line_gap)
    for i, line in enumerate(lines):
        bb = draw.textbbox((0, 0), line, font=font, stroke_width=stroke_width)
        tw = bb[2] - bb[0]
        x = (W - tw) // 2
        draw.text((x, y + i * line_h), line, font=font, fill=fill, stroke_width=stroke_width, stroke_fill=stroke_fill)
    return len(lines) * line_h


def scene_at(t: float):
    for start, end, kind, title, subtitle, screen in SCENE_PLAN:
        if start <= t < end:
            local = (t - start) / max(0.001, end - start)
            return kind, title, subtitle, screen, local, start, end
    s = SCENE_PLAN[-1]
    return s[2], s[3], s[4], s[5], 1.0, s[0], s[1]


def load_screen(name: str | None) -> Image.Image | None:
    if not name:
        return None
    path = SCREENS / name
    if path.exists():
        return Image.open(path).convert("RGB")
    # fallbacks
    for cand in sorted(SCREENS.glob("4*.png")):
        return Image.open(cand).convert("RGB")
    return None


def render_frame(t: float, logo: Image.Image, clip_frame: Image.Image | None) -> Image.Image:
    img = Image.new("RGB", (W, H))
    draw_gradient(img, t)
    kind, title, subtitle, screen_name, local, _, _ = scene_at(t)

    # Prefer live clip under app scenes when available
    screen = None
    if kind == "app" and clip_frame is not None:
        screen = clip_frame
    else:
        screen = load_screen(screen_name)

    if screen is not None and kind in {"hook", "app", "cta"}:
        overlay = phone_frame(screen, t, local)
        img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")

    draw = ImageDraw.Draw(img)

    # top chip
    chip_font = load_font(30, bold=True)
    chip = "DATECONTA FACTURARE"
    chip_w = draw.textlength(chip, font=chip_font)
    cx0 = (W - chip_w) / 2 - 26
    draw.rounded_rectangle([cx0, 70, cx0 + chip_w + 52, 128], radius=28, fill=WHITE)
    draw.text((cx0 + 26, 84), chip, font=chip_font, fill=TEAL)

    # small logo
    lw = 96
    logo_r = logo.resize((lw, lw), Image.Resampling.LANCZOS)
    img = img.convert("RGBA")
    img.alpha_composite(logo_r.convert("RGBA"), ((W - lw) // 2, 148))
    img = img.convert("RGB")
    draw = ImageDraw.Draw(img)

    if kind == "launch":
        pulse = 0.5 + 0.5 * math.sin(t * 14)
        star_layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
        sd = ImageDraw.Draw(star_layer)
        draw_star(sd, W / 2, 820, 230 + 28 * pulse, fill=(255, int(140 + 80 * pulse), 30, 240), outline=(255, 248, 196, 255), width=8)
        img = Image.alpha_composite(img.convert("RGBA"), star_layer).convert("RGB")
        draw = ImageDraw.Draw(img)
        center_text(draw, ["LANSARE!!!"], load_font(88, True), 740, fill=(90, 20, 0), stroke_fill=WHITE, stroke_width=2)
        center_text(draw, ["15 august 2026 · ora 10:00"], load_font(46, True), 980, fill=WHITE, stroke_fill=TEAL, stroke_width=3)
        center_text(draw, ["Poți testa acum · prelansare"], load_font(34, True), 1080, fill=AMBER_HOT)
    else:
        # lower third text over app footage
        band = Image.new("RGBA", (W, H), (0, 0, 0, 0))
        bd = ImageDraw.Draw(band)
        bd.rectangle([0, 1480, W, H], fill=(8, 28, 36, 210))
        img = Image.alpha_composite(img.convert("RGBA"), band).convert("RGB")
        draw = ImageDraw.Draw(img)
        title_font = load_font(64, True)
        lines = title.split("\n")
        center_text(draw, lines, title_font, 1520, fill=WHITE, stroke_fill=(0, 0, 0), stroke_width=2)
        center_text(draw, [subtitle], load_font(34, False), 1660, fill=CREAM)

    if kind == "cta":
        bw, bh = 820, 120
        bx, by = (W - bw) // 2, 1720
        pulse = 0.5 + 0.5 * math.sin(t * 8)
        draw.rounded_rectangle([bx - 4, by - 4, bx + bw + 4, by + bh + 4], radius=36, fill=(255, int(160 + 50 * pulse), 40))
        draw.rounded_rectangle([bx, by, bx + bw, by + bh], radius=32, fill=AMBER)
        center_text(draw, ["factura.dateconta.ro"], load_font(48, True), by + 34, fill=WHITE)

    # progress
    prog = max(0.0, min(1.0, t / TARGET_DUR))
    draw.rectangle([0, H - 14, W, H], fill=(0, 0, 0))
    draw.rectangle([0, H - 14, int(W * prog), H], fill=AMBER_HOT)
    return img


def make_voiceover(path: Path) -> float:
    """Cheerful neural Romanian voice (edge-tts Alina) — warmer, less robotic than macOS say."""
    import asyncio

    import edge_tts

    mp3 = path.with_suffix(".mp3")
    raw = path.with_name(path.stem + "_raw.wav")

    async def _synth() -> None:
        # +rate / slight +pitch = more energetic, optimistic delivery
        communicate = edge_tts.Communicate(
            VOICE_TEXT,
            voice="ro-RO-AlinaNeural",
            rate="+10%",
            pitch="+3Hz",
        )
        await communicate.save(str(mp3))

    asyncio.run(_synth())

    ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
    subprocess.run(
        [ffmpeg, "-y", "-i", str(mp3), "-ac", "1", "-ar", "44100", str(raw)],
        check=True,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )
    # Warm human tone: body up, harsh “metal” highs down, gentle room
    subprocess.run(
        [
            ffmpeg,
            "-y",
            "-i",
            str(raw),
            "-af",
            ",".join(
                [
                    "highpass=f=70",
                    "lowpass=f=8500",
                    "equalizer=f=220:t=q:w=0.9:g=3.5",
                    "equalizer=f=1200:t=q:w=1:g=1.2",
                    "equalizer=f=3500:t=q:w=1.2:g=-4",
                    "equalizer=f=6000:t=q:w=1:g=-5",
                    "acompressor=threshold=-18dB:ratio=2.2:attack=15:release=220:makeup=3",
                    "aecho=0.8:0.7:28:0.12",
                    "volume=1.9",
                    "alimiter=limit=0.96",
                ]
            ),
            "-ac",
            "1",
            "-ar",
            "44100",
            str(path),
        ],
        check=True,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )
    mp3.unlink(missing_ok=True)
    raw.unlink(missing_ok=True)
    return probe_duration(path)


def make_music_bed(path: Path, duration: float, sr: int = 44100) -> None:
    n = int(duration * sr)
    t = np.arange(n, dtype=np.float64) / sr
    rng = np.random.default_rng(7)
    bpm = 100.0
    bar = 4 * 60.0 / bpm
    chords = [
        [130.81, 164.81, 196.00],
        [146.83, 174.61, 220.00],
        [110.00, 146.83, 174.61],
        [98.00, 123.47, 146.83],
    ]
    pad = np.zeros(n)
    chord_len = int(bar * 2 * sr)
    pos = 0
    ci = 0
    while pos < n:
        chunk = min(chord_len, n - pos)
        te = np.arange(chunk) / sr
        e = np.linspace(0, 1, min(int(0.2 * sr), chunk), endpoint=False)
        env = np.ones(chunk)
        env[: len(e)] = e
        env[-int(0.25 * sr) :] *= np.linspace(1, 0.7, int(0.25 * sr))[: max(0, chunk)]
        for f in chords[ci % len(chords)]:
            wave_ = 0.55 * np.sin(2 * np.pi * f * te) + 0.2 * np.sin(2 * np.pi * f * 2 * te)
            pad[pos : pos + chunk] += wave_ * env
        pos += chunk
        ci += 1
    pad /= np.max(np.abs(pad)) + 1e-9
    pad *= 0.38

    mel_notes = [523.25, 659.25, 784.0, 659.25, 587.33, 523.25, 440.0, 523.25]
    step = 60.0 / bpm
    mel = np.zeros(n)
    for i, f in enumerate(mel_notes * 20):
        start = int(i * step * sr)
        if start >= n:
            break
        length = int(0.4 * sr)
        end = min(n, start + length)
        te = np.arange(end - start) / sr
        mel[start:end] += 0.16 * np.sin(2 * np.pi * f * te) * np.exp(-te * 3.8)

    beat = np.zeros(n)
    beat_step = int((60.0 / bpm) * sr)
    for i in range(0, n, beat_step):
        klen = min(int(0.1 * sr), n - i)
        te = np.arange(klen) / sr
        beat[i : i + klen] += 0.26 * np.sin(2 * np.pi * (85 * np.exp(-te * 30)) * te) * np.exp(-te * 16)
        j = i + beat_step // 2
        if j < n:
            hlen = min(int(0.035 * sr), n - j)
            noise = rng.normal(0, 1, hlen) * np.exp(-np.arange(hlen) / sr * 70)
            beat[j : j + hlen] += 0.05 * noise

    mix = pad + mel + beat
    fade = int(0.7 * sr)
    mix[:fade] *= np.linspace(0, 1, fade)
    mix[-fade:] *= np.linspace(1, 0, fade)
    mix /= np.max(np.abs(mix)) + 1e-9
    pcm = (mix * 0.95 * 32767).astype(np.int16)
    with wave.open(str(path), "wb") as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(sr)
        wf.writeframes(pcm.tobytes())


def mix_voice_and_music(voice: Path, music: Path, out: Path, duration: float) -> None:
    ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
    voice_pad = ASSETS / "voice_padded.wav"
    subprocess.run(
        [ffmpeg, "-y", "-i", str(voice), "-af", f"apad=whole_dur={duration:.3f}", "-t", f"{duration:.3f}", "-ac", "1", "-ar", "44100", str(voice_pad)],
        check=True,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )
    fade_out_at = max(0.0, duration - 1.5)
    # Louder music bed; gentle duck only — voice still clear
    filt = (
        f"[0:a]aformat=sample_fmts=fltp:channel_layouts=mono,volume=1.25[voc];"
        f"[1:a]aformat=sample_fmts=fltp:channel_layouts=mono,"
        f"aloop=loop=-1:size=2000000000,atrim=0:{duration:.3f},"
        f"afade=t=in:st=0:d=0.4,afade=t=out:st={fade_out_at:.3f}:d=1.5,volume=0.72[bed];"
        f"[bed][voc]sidechaincompress=threshold=0.06:ratio=3.2:attack=50:release=520:makeup=1.6:level_sc=0.75[ducked];"
        f"[voc][ducked]amix=inputs=2:duration=longest:dropout_transition=0:weights=1.15 1.25,"
        f"alimiter=limit=0.98,volume=2.1,alimiter=limit=0.99,atrim=0:{duration:.3f}[aout]"
    )
    r = subprocess.run(
        [ffmpeg, "-y", "-i", str(voice_pad), "-i", str(music), "-filter_complex", filt, "-map", "[aout]", "-t", f"{duration:.3f}", "-ac", "2", "-ar", "44100", str(out)],
        capture_output=True,
        text=True,
    )
    if r.returncode != 0:
        raise RuntimeError(r.stderr[-2500:])


def prepare_app_clip(dest: Path, duration: float) -> Path | None:
    clips = sorted((SCREENS / "clips").glob("*.webm"))
    if not clips:
        return None
    ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
    src = clips[-1]
    # Loop/trim clip to cover timeline for app scenes
    subprocess.run(
        [
            ffmpeg,
            "-y",
            "-stream_loop",
            "-1",
            "-i",
            str(src),
            "-t",
            f"{duration:.3f}",
            "-vf",
            "scale=1440:900:force_original_aspect_ratio=increase,crop=1440:900,fps=30",
            "-an",
            "-c:v",
            "libx264",
            "-pix_fmt",
            "yuv420p",
            str(dest),
        ],
        check=True,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )
    return dest


def main():
    ASSETS.mkdir(parents=True, exist_ok=True)
    logo = Image.open(LOGO).convert("RGBA")

    print("Voiceover...")
    voice_path = ASSETS / "voiceover.wav"
    voice_dur = make_voiceover(voice_path)
    total = max(voice_dur + 2.5, 40.0)
    total = min(45.0, total)
    # stretch last scene end
    global SCENE_PLAN
    SCENE_PLAN = list(SCENE_PLAN)
    start, _, kind, title, subtitle, screen = SCENE_PLAN[-1]
    SCENE_PLAN[-1] = (start, total, kind, title, subtitle, screen)
    global TARGET_DUR
    TARGET_DUR = total
    print(f"voice={voice_dur:.2f}s video={total:.2f}s")

    print("Music...")
    music_path = ASSETS / "music_bed.wav"
    make_music_bed(music_path, total + 1.0)

    print("Mix audio (loud)...")
    mixed = ASSETS / "mix.wav"
    mix_voice_and_music(voice_path, music_path, mixed, total)

    print("Prepare app clip...")
    app_clip = prepare_app_clip(ASSETS / "app_loop.mp4", total)
    clip_cap = cv2.VideoCapture(str(app_clip)) if app_clip else None

    silent = ASSETS / "video_silent.mp4"
    writer = cv2.VideoWriter(str(silent), cv2.VideoWriter_fourcc(*"mp4v"), FPS, (W, H))
    n_frames = int(total * FPS)
    print(f"Render {n_frames} frames...")
    for i in range(n_frames):
        t = i / FPS
        clip_frame = None
        if clip_cap is not None:
            ok, frame = clip_cap.read()
            if not ok:
                clip_cap.set(cv2.CAP_PROP_POS_FRAMES, 0)
                ok, frame = clip_cap.read()
            if ok:
                clip_frame = Image.fromarray(cv2.cvtColor(frame, cv2.COLOR_BGR2RGB))
        frame_img = render_frame(t, logo, clip_frame)
        writer.write(cv2.cvtColor(np.array(frame_img), cv2.COLOR_RGB2BGR))
        if i % 90 == 0:
            print(f"  {i}/{n_frames}")
    writer.release()
    if clip_cap is not None:
        clip_cap.release()

    ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
    print("Mux final...")
    subprocess.run(
        [
            ffmpeg,
            "-y",
            "-i",
            str(silent),
            "-i",
            str(mixed),
            "-c:v",
            "libx264",
            "-preset",
            "fast",
            "-crf",
            "18",
            "-pix_fmt",
            "yuv420p",
            "-c:a",
            "aac",
            "-b:a",
            "256k",
            "-ac",
            "2",
            "-ar",
            "44100",
            "-t",
            f"{total:.3f}",
            "-movflags",
            "+faststart",
            str(OUT_MP4),
        ],
        check=True,
    )

    # Extra loudness pass on final file (fixes "no sound" perception)
    loud = ASSETS / "final_loud.mp4"
    subprocess.run(
        [
            ffmpeg,
            "-y",
            "-i",
            str(OUT_MP4),
            "-af",
            "loudnorm=I=-8:TP=-0.8:LRA=6,volume=1.35,alimiter=limit=0.99",
            "-c:v",
            "copy",
            "-c:a",
            "aac",
            "-b:a",
            "256k",
            "-movflags",
            "+faststart",
            str(loud),
        ],
        check=True,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )
    loud.replace(OUT_MP4)
    DESKTOP_COPY.write_bytes(OUT_MP4.read_bytes())

    # Verify volume
    det = subprocess.run([ffmpeg, "-i", str(OUT_MP4), "-af", "volumedetect", "-f", "null", "-"], capture_output=True, text=True)
    for line in det.stderr.splitlines():
        if "mean_volume" in line or "max_volume" in line:
            print(line.strip())
    print(f"Done: {OUT_MP4} ({OUT_MP4.stat().st_size/1e6:.1f} MB)")
    print(f"Desktop: {DESKTOP_COPY}")


if __name__ == "__main__":
    main()
