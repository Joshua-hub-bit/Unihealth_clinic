from __future__ import annotations

import argparse
import json
import logging
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from typing import Any

from faster_whisper import WhisperModel


LOGGER = logging.getLogger("clinic.whisper")
MAX_REQUEST_BYTES = 8_192
ALLOWED_AUDIO_SUFFIXES = {".m4a", ".mp3", ".ogg", ".wav", ".webm"}


class WhisperTranscriber:
    def __init__(
        self,
        model: Any,
        audio_root: Path,
        task: str,
        language: str | None,
        beam_size: int,
        initial_prompt: str | None,
        hotwords: str | None,
    ) -> None:
        self.model = model
        self.audio_root = audio_root.resolve(strict=True)
        self.task = task
        self.language = language
        self.beam_size = beam_size
        self.initial_prompt = initial_prompt
        self.hotwords = hotwords
        self.model_lock = threading.Lock()

    def transcribe(self, audio_path: str) -> str:
        path = Path(audio_path).resolve(strict=True)
        relative_path = path.relative_to(self.audio_root)
        if not relative_path.parts or relative_path.parts[0] != "consultations":
            raise ValueError("Only consultation recordings can be transcribed.")
        if not path.is_file() or path.suffix.lower() not in ALLOWED_AUDIO_SUFFIXES:
            raise ValueError("The audio file is not an allowed consultation recording.")

        with self.model_lock:
            segments, _ = self.model.transcribe(
                str(path),
                language=self.language,
                task=self.task,
                beam_size=self.beam_size,
                temperature=0.0,
                vad_filter=True,
                initial_prompt=self.initial_prompt,
                hotwords=self.hotwords,
            )
            return "".join(segment.text for segment in segments).strip()


class WhisperHttpServer(ThreadingHTTPServer):
    daemon_threads = True

    def __init__(self, address: tuple[str, int], transcriber: WhisperTranscriber) -> None:
        self.transcriber = transcriber
        super().__init__(address, WhisperRequestHandler)


class WhisperRequestHandler(BaseHTTPRequestHandler):
    server: WhisperHttpServer

    def do_GET(self) -> None:
        if self.path != "/health":
            self._send_json(404, {"error": "Not found."})
            return

        self._send_json(200, {"status": "ready"})

    def do_POST(self) -> None:
        if self.path != "/transcribe":
            self._send_json(404, {"error": "Not found."})
            return

        content_length = self.headers.get("Content-Length")
        if not content_length or not content_length.isdigit():
            self._send_json(400, {"error": "A valid JSON request is required."})
            return

        if int(content_length) > MAX_REQUEST_BYTES:
            self._send_json(413, {"error": "The request is too large."})
            return

        try:
            payload = json.loads(self.rfile.read(int(content_length)))
        except (json.JSONDecodeError, UnicodeDecodeError):
            self._send_json(400, {"error": "A valid JSON request is required."})
            return

        audio_path = payload.get("audio_path") if isinstance(payload, dict) else None
        if not isinstance(audio_path, str) or not audio_path:
            self._send_json(422, {"error": "The audio path is required."})
            return

        try:
            transcript = self.server.transcriber.transcribe(audio_path)
        except (OSError, RuntimeError, ValueError):
            LOGGER.exception("Local consultation transcription failed.")
            self._send_json(500, {"error": "The local transcription failed."})
            return

        self._send_json(200, {"text": transcript})

    def log_message(self, format_string: str, *args: Any) -> None:
        LOGGER.info("%s - %s", self.address_string(), format_string % args)

    def _send_json(self, status: int, payload: dict[str, str]) -> None:
        response = json.dumps(payload).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(response)))
        self.send_header("Cache-Control", "no-store")
        self.end_headers()
        self.wfile.write(response)


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Persistent local faster-whisper service.")
    parser.add_argument("--host", choices=["127.0.0.1"], default="127.0.0.1")
    parser.add_argument("--port", type=int, default=8765)
    parser.add_argument("--model", default="large-v3")
    parser.add_argument("--device", choices=["cpu", "cuda", "auto"], default="cpu")
    parser.add_argument("--compute-type", default="int8")
    parser.add_argument("--threads", type=int, default=4)
    parser.add_argument("--beam-size", type=int, default=3)
    parser.add_argument("--task", choices=["transcribe", "translate"], default="translate")
    parser.add_argument("--language", default="")
    parser.add_argument("--initial-prompt", default="")
    parser.add_argument("--hotwords", default="")
    parser.add_argument("--audio-root", required=True)
    return parser.parse_args()


def main() -> None:
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    args = parse_arguments()

    if args.port < 1 or args.port > 65535 or args.threads < 1 or args.beam_size < 1:
        raise SystemExit("Port, thread count, and beam size must be positive valid values.")

    LOGGER.info("Loading faster-whisper model %s; this may take time on the first launch.", args.model)
    model = WhisperModel(
        args.model,
        device=args.device,
        compute_type=args.compute_type,
        cpu_threads=args.threads,
    )
    transcriber = WhisperTranscriber(
        model=model,
        audio_root=Path(args.audio_root),
        task=args.task,
        language=args.language or None,
        beam_size=args.beam_size,
        initial_prompt=args.initial_prompt or None,
        hotwords=args.hotwords or None,
    )
    server = WhisperHttpServer((args.host, args.port), transcriber)
    LOGGER.info("Whisper daemon ready on %s:%s.", args.host, args.port)

    try:
        server.serve_forever()
    except KeyboardInterrupt:
        LOGGER.info("Whisper daemon stopping.")
    finally:
        server.server_close()


if __name__ == "__main__":
    main()
