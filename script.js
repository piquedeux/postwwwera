document.addEventListener("DOMContentLoaded", () => {
  const lightbox = document.getElementById("lightbox");
  const lightboxContent = document.getElementById("lightboxContent");
  const lightboxClose = document.getElementById("lightboxClose");
  const tiles = Array.from(document.querySelectorAll("[data-gallery-tile]"));
  const previewVideos = Array.from(
    document.querySelectorAll("[data-gallery-preview-video]"),
  );

  function formatTime(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) {
      return "0:00";
    }
    const total = Math.floor(seconds);
    const minutes = Math.floor(total / 60);
    const remaining = total % 60;
    return `${minutes}:${String(remaining).padStart(2, "0")}`;
  }

  function updateVideoPreview(video) {
    try {
      video.muted = true;
      video.playsInline = true;
      video.autoplay = true;
      video.loop = true;
      video.preload = "auto";
      video.play().catch(() => {});
    } catch (error) {
      // Ignore autoplay failures on restricted browsers.
    }
  }

  function stopPreviewVideos() {
    previewVideos.forEach((video) => {
      try {
        video.pause();
        video.currentTime = 0;
      } catch (error) {
        // Ignore preview playback errors.
      }
    });
  }

  function createMediaControls(media, options = {}) {
    const controls = document.createElement("div");
    controls.className = "media-controls";

    const playButton = document.createElement("button");
    playButton.type = "button";
    playButton.className = "media-control media-control-play";
    playButton.textContent = "Play";

    const progress = document.createElement("input");
    progress.type = "range";
    progress.className = "media-progress";
    progress.min = "0";
    progress.max = "100";
    progress.value = "0";
    progress.step = "0.1";

    const timeLabel = document.createElement("span");
    timeLabel.className = "media-time";
    timeLabel.textContent = "0:00 / 0:00";

    const muteButton = document.createElement("button");
    muteButton.type = "button";
    muteButton.className = "media-control media-control-mute";
    muteButton.textContent = options.allowMute === false ? "Audio" : "Mute";

    const updateState = () => {
      playButton.textContent = media.paused ? "Play" : "Pause";
      if (options.allowMute !== false) {
        muteButton.textContent = media.muted ? "Unmute" : "Mute";
      }
      const current = Number.isFinite(media.currentTime)
        ? media.currentTime
        : 0;
      const duration = Number.isFinite(media.duration) ? media.duration : 0;
      progress.value = duration > 0 ? String((current / duration) * 100) : "0";
      timeLabel.textContent = `${formatTime(current)} / ${formatTime(duration)}`;
    };

    playButton.addEventListener("click", () => {
      if (media.paused) {
        media.play().catch(() => {});
      } else {
        media.pause();
      }
    });

    progress.addEventListener("input", () => {
      if (!Number.isFinite(media.duration) || media.duration <= 0) {
        return;
      }
      media.currentTime = (Number(progress.value) / 100) * media.duration;
    });

    if (options.allowMute !== false) {
      muteButton.addEventListener("click", () => {
        media.muted = !media.muted;
        updateState();
      });
    }

    media.addEventListener("loadedmetadata", updateState);
    media.addEventListener("timeupdate", updateState);
    media.addEventListener("play", updateState);
    media.addEventListener("pause", updateState);
    media.addEventListener("volumechange", updateState);

    controls.appendChild(playButton);
    controls.appendChild(progress);
    controls.appendChild(timeLabel);
    if (options.allowMute !== false) {
      controls.appendChild(muteButton);
    }

    updateState();
    return controls;
  }

  function enhanceAudioPlayers() {
    document.querySelectorAll(".audio-card audio").forEach((audio) => {
      if (audio.dataset.enhanced === "1") {
        return;
      }
      audio.dataset.enhanced = "1";
      audio.hidden = true;
      const card = audio.closest(".audio-card");
      if (!card) {
        return;
      }
      const controls = createMediaControls(audio, { allowMute: true });
      card.appendChild(controls);
    });
  }

  function closeLightbox() {
    if (!lightbox) {
      return;
    }
    lightbox.classList.remove("open");
    lightbox.setAttribute("aria-hidden", "true");
    if (lightboxContent) {
      lightboxContent.innerHTML = "";
    }
  }

  function openLightbox(tile) {
    if (!lightbox || !lightboxContent) {
      return;
    }

    const type = tile.dataset.type || "image";
    const src = tile.dataset.src || "";

    stopPreviewVideos();
    lightboxContent.innerHTML = "";

    const panel = document.createElement("div");
    panel.className = "lightbox-panel";

    const mediaWrap = document.createElement("div");
    mediaWrap.className = "lightbox-media";

    if (type === "video") {
      const video = document.createElement("video");
      video.src = src;
      video.autoplay = true;
      video.loop = true;
      video.playsInline = true;
      video.preload = "metadata";
      mediaWrap.appendChild(video);
      mediaWrap.appendChild(createMediaControls(video, { allowMute: true }));
      setTimeout(() => {
        video.play().catch(() => {});
      }, 0);
    } else {
      const image = document.createElement("img");
      image.src = src;
      image.alt = "";
      mediaWrap.appendChild(image);
    }

    panel.appendChild(mediaWrap);
    lightboxContent.appendChild(panel);
    lightbox.classList.add("open");
    lightbox.setAttribute("aria-hidden", "false");

    if (type === "video") {
      const video = mediaWrap.querySelector("video");
      if (video) {
        video.play().catch(() => {});
      }
    }
  }

  tiles.forEach((tile) => {
    tile.addEventListener("click", () => openLightbox(tile));
  });

  if (lightboxClose) {
    lightboxClose.addEventListener("click", closeLightbox);
  }

  if (lightbox) {
    lightbox.addEventListener("click", (event) => {
      if (event.target === lightbox) {
        closeLightbox();
      }
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeLightbox();
    }
  });

  if ("IntersectionObserver" in window && previewVideos.length) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            updateVideoPreview(entry.target);
          } else {
            try {
              entry.target.pause();
            } catch (error) {
              // Ignore.
            }
          }
        });
      },
      { threshold: 0.2 },
    );
    previewVideos.forEach((video) => observer.observe(video));
  } else {
    previewVideos.forEach(updateVideoPreview);
  }

  enhanceAudioPlayers();
  previewVideos.forEach(updateVideoPreview);
});
