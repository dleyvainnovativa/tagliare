/* ============================================================
   player.js — public audio player
   One Audio() instance, playlist navigation, scrub, autoplay-next.
   Reads track data from a JSON script tag (#playlist-data).
   ============================================================ */

(function () {
  const root = document.getElementById('player');
  if (!root) return;

  const tracks = JSON.parse(document.getElementById('playlist-data').textContent);
  if (!tracks.length) return;

  // --- DOM refs ---
  const els = {
    cover:    root.querySelector('[data-cover]'),
    title:    root.querySelector('[data-track-title]'),
    counter:  root.querySelector('[data-counter]'),
    playBtn:  root.querySelector('[data-play]'),
    playIcon: root.querySelector('[data-play] i'),
    prevBtn:  root.querySelector('[data-prev]'),
    nextBtn:  root.querySelector('[data-next]'),
    seek:     root.querySelector('[data-seek]'),
    seekFill: root.querySelector('[data-seek-fill]'),
    seekThumb: root.querySelector('[data-seek-thumb]'),
    seekWrap: root.querySelector('[data-seek-wrap]'),
    cur:      root.querySelector('[data-current-time]'),
    dur:      root.querySelector('[data-duration]'),
    list:     root.querySelector('[data-list]'),
  };

  const audio = new Audio();
  audio.preload = 'metadata';
  let index = 0;
  let seeking = false;

  const fmt = (s) => {
    if (!s || isNaN(s)) return '0:00';
    const m = Math.floor(s / 60);
    const sec = Math.floor(s % 60).toString().padStart(2, '0');
    return `${m}:${sec}`;
  };

  function load(i, autoplay = false) {
    index = (i + tracks.length) % tracks.length; // wrap both directions
    const t = tracks[index];
    audio.src = t.src;

    els.title.textContent = t.title;
    els.counter.textContent = `${index + 1} / ${tracks.length}`;
    // Fallback duration from metadata until the file reports its own
    els.dur.textContent = fmt(t.duration);
    setProgress(0);
    els.seek.value = 0;
    els.cur.textContent = '0:00';

    highlight();
    if (autoplay) play();
  }

  // Update fill width + thumb position together
  function setProgress(pct) {
    pct = Math.max(0, Math.min(100, pct));
    els.seekFill.style.width = `${pct}%`;
    if (els.seekThumb) els.seekThumb.style.left = `${pct}%`;
  }

  function play() {
    audio.play().then(() => {
      els.playIcon.className = 'fa-solid fa-pause';
      root.classList.add('is-playing');
    }).catch(() => {
      // Autoplay blocked or source error — reset to paused state
      els.playIcon.className = 'fa-solid fa-play';
    });
  }

  function pause() {
    audio.pause();
    els.playIcon.className = 'fa-solid fa-play';
    root.classList.remove('is-playing');
  }

  function toggle() {
    audio.paused ? play() : pause();
  }

  function highlight() {
    els.list.querySelectorAll('[data-row]').forEach((row, i) => {
      row.classList.toggle('is-active', i === index);
    });
  }

  // --- Events: controls ---
  els.playBtn.addEventListener('click', toggle);
  els.nextBtn.addEventListener('click', () => load(index + 1, true));
  els.prevBtn.addEventListener('click', () => {
    // If more than 3s in, restart current track instead of going back
    if (audio.currentTime > 3) { audio.currentTime = 0; return; }
    load(index - 1, true);
  });

  // --- Events: audio element ---
  audio.addEventListener('loadedmetadata', () => {
    if (isFinite(audio.duration)) els.dur.textContent = fmt(audio.duration);
  });

  audio.addEventListener('timeupdate', () => {
    if (seeking || !isFinite(audio.duration)) return;
    const pct = (audio.currentTime / audio.duration) * 100;
    setProgress(pct);
    els.seek.value = pct;
    els.cur.textContent = fmt(audio.currentTime);
  });

  audio.addEventListener('ended', () => load(index + 1, true));

  audio.addEventListener('error', () => {
    els.title.textContent = 'No se pudo cargar este audio';
    pause();
  });

  // --- Events: seek bar ---
  // Toggle 1:1 drag mode (drops CSS transition while the finger moves)
  els.seek.addEventListener('pointerdown', () => {
    seeking = true;
    els.seekWrap?.classList.add('is-seeking');
  });
  const endSeek = () => {
    if (!seeking) return;
    if (isFinite(audio.duration)) {
      audio.currentTime = (els.seek.value / 100) * audio.duration;
    }
    seeking = false;
    els.seekWrap?.classList.remove('is-seeking');
  };
  els.seek.addEventListener('pointerup', endSeek);
  els.seek.addEventListener('pointercancel', endSeek);

  els.seek.addEventListener('input', () => {
    seeking = true;
    setProgress(parseFloat(els.seek.value));
    if (isFinite(audio.duration)) {
      els.cur.textContent = fmt((els.seek.value / 100) * audio.duration);
    }
  });
  // Fallback for keyboard / non-pointer commits
  els.seek.addEventListener('change', endSeek);

  // --- Events: track list click ---
  els.list.querySelectorAll('[data-row]').forEach((row, i) => {
    row.addEventListener('click', () => load(i, true));
  });

  // --- Keyboard: space = play/pause, arrows = seek/skip ---
  document.addEventListener('keydown', (e) => {
    if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
    if (e.code === 'Space') { e.preventDefault(); toggle(); }
    if (e.code === 'ArrowRight') audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + 10);
    if (e.code === 'ArrowLeft')  audio.currentTime = Math.max(0, audio.currentTime - 10);
  });

  // --- Media Session (lock screen / headphone controls) ---
  if ('mediaSession' in navigator) {
    audio.addEventListener('play', () => {
      const t = tracks[index];
      navigator.mediaSession.metadata = new MediaMetadata({
        title: t.title,
        artist: root.dataset.playlistTitle || 'Audios',
        artwork: root.dataset.cover ? [{ src: root.dataset.cover, sizes: '512x512' }] : [],
      });
    });
    navigator.mediaSession.setActionHandler('play', play);
    navigator.mediaSession.setActionHandler('pause', pause);
    navigator.mediaSession.setActionHandler('previoustrack', () => load(index - 1, true));
    navigator.mediaSession.setActionHandler('nexttrack', () => load(index + 1, true));
  }

  // --- Init ---
  load(0, false);
})();
