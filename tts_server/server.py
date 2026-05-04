"""
TTS Server — Flask microservice for voice cloning with Coqui XTTS v2.
Loads a reference audio file and synthesizes speech in the cloned voice.

Usage:
    pip install -r requirements.txt
    python server.py
"""

import os
import io
import hashlib
from flask import Flask, request, send_file, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# Paths
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
REFERENCE_VOICE = os.path.join(BASE_DIR, "reference_voice", "voice.wav")
CACHE_DIR = os.path.join(BASE_DIR, "cache")

os.makedirs(CACHE_DIR, exist_ok=True)

# Lazy-load TTS model
tts_model = None


def get_model():
    """Lazy-load the XTTS v2 model (downloads on first run)."""
    global tts_model
    if tts_model is None:
        print("[TTS] Loading XTTS v2 model... (this may take a moment)")

        from TTS.api import TTS
        device = "cuda" if _has_cuda() else "cpu"
        print(f"[TTS] Using device: {device}")
        tts_model = TTS("tts_models/multilingual/multi-dataset/xtts_v2").to(device)
        print("[TTS] Model loaded successfully!")
    return tts_model


def _has_cuda():
    try:
        import torch
        return torch.cuda.is_available()
    except ImportError:
        return False


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "model": "xtts_v2"})


@app.route("/synthesize", methods=["POST"])
def synthesize():
    data = request.get_json()
    text = data.get("text", "").strip()
    language = data.get("language", "es")

    if not text:
        return jsonify({"error": "No text provided"}), 400

    if not os.path.exists(REFERENCE_VOICE):
        return jsonify({"error": "Reference voice not found"}), 500

    # Check cache
    cache_key = hashlib.md5(f"{text}_{language}".encode()).hexdigest()
    cache_path = os.path.join(CACHE_DIR, f"{cache_key}.wav")

    if os.path.exists(cache_path):
        return send_file(cache_path, mimetype="audio/wav")

    try:
        model = get_model()
        model.tts_to_file(
            text=text,
            speaker_wav=REFERENCE_VOICE,
            language=language,
            file_path=cache_path,
        )
        return send_file(cache_path, mimetype="audio/wav")
    except Exception as e:
        import traceback
        traceback.print_exc()
        print(f"[TTS] Error: {e}")
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    print(f"[TTS] Reference voice: {REFERENCE_VOICE}")
    print(f"[TTS] Cache dir: {CACHE_DIR}")
    print(f"[TTS] Starting TTS server on http://127.0.0.1:5050")
    app.run(host="127.0.0.1", port=5050, debug=False)
