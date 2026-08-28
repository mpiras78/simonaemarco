/* =====================================================================
   PARTECIPAZIONE DI NOZZE — script.js
   Gestisce: apertura busta animata, countdown, apertura form RSVP
   e invio dati tramite PHP (fetch AJAX, senza ricaricare la pagina).
===================================================================== */

document.addEventListener('DOMContentLoaded', () => {

  /* -------------------------------------------------------------
     0) CONTROLLO AUDIO DI SOTTOFONDO
  ------------------------------------------------------------- */
  const bgMusic = document.getElementById('bgMusic');
  const audioToggle = document.getElementById('audioToggle');
  let isMuted = false;

  audioToggle.addEventListener('click', () => {
    isMuted = !isMuted;
    if (isMuted) {
      bgMusic.pause();
      audioToggle.classList.add('muted');
    } else {
      bgMusic.play().catch(() => {});
      audioToggle.classList.remove('muted');
    }
  });

  /* Tentare di avviare la musica al primo click sulla pagina */
  document.addEventListener('click', function attemptPlay() {
    bgMusic.play().catch(() => {});
    document.removeEventListener('click', attemptPlay);
  }, { once: true });

  /* -------------------------------------------------------------
     1) APERTURA BUSTA -> MOSTRA VIDEO -> MOSTRA IL SITO
  ------------------------------------------------------------- */
  const openButton = document.getElementById('openButton');
  const envelopeScreen = document.getElementById('envelope-screen');
  const envelopeOverlay = document.querySelector('.envelope-overlay');
  const openingVideo = document.getElementById('openingVideo');
  const mainSite = document.getElementById('main-site');
  let isOpening = false;

  openButton.addEventListener('click', () => {
    if (isOpening) return;
    isOpening = true;

    // Nascondi overlay e mostra video
    envelopeOverlay.classList.add('hidden');
    openingVideo.classList.add('playing');
    
    // Riproduci il video
    openingVideo.play();

    // Quando il video finisce, mostra il sito principale
    openingVideo.onended = () => {
      envelopeScreen.classList.add('hidden');
      mainSite.classList.add('visible');
      document.body.style.overflowY = 'auto';
      startCountdown();
    };

    // Fallback: se il video non finisce entro 5 secondi, passa comunque
    setTimeout(() => {
      if (!envelopeScreen.classList.contains('hidden')) {
        envelopeScreen.classList.add('hidden');
        mainSite.classList.add('visible');
        document.body.style.overflowY = 'auto';
        startCountdown();
      }
    }, 5000);
  });

  // Blocca lo scroll finché la busta non è aperta
  document.body.style.overflowY = 'hidden';

  /* -------------------------------------------------------------
     2) COUNTDOWN
  ------------------------------------------------------------- */
  // Data e ora dell'evento: 04 Luglio 2027, ore 17:30
  const weddingDate = new Date('2027-07-04T17:30:00');
  let countdownInterval = null;

  function startCountdown() {
    if (countdownInterval) return;
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
  }

  function updateCountdown() {
    const now = new Date();
    let diff = weddingDate - now;

    if (diff <= 0) {
      diff = 0;
      clearInterval(countdownInterval);
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
    const minutes = Math.floor((diff / (1000 * 60)) % 60);
    const seconds = Math.floor((diff / 1000) % 60);

    setText('cd-days', days);
    setText('cd-hours', hours);
    setText('cd-minutes', minutes);
    setText('cd-seconds', seconds);
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value).padStart(2, '0');
  }

  /* -------------------------------------------------------------
     3) MODALE FORM RSVP
  ------------------------------------------------------------- */
  const rsvpModal = document.getElementById('rsvpModal');
  const openRsvpBtn = document.getElementById('openRsvpForm');
  const closeRsvpBtn = document.getElementById('closeRsvpForm');

  openRsvpBtn.addEventListener('click', () => {
    rsvpModal.classList.add('open');
    document.body.style.overflow = 'hidden';
  });

  closeRsvpBtn.addEventListener('click', closeModal);

  function closeModal() {
    rsvpModal.classList.remove('open');
    document.body.style.overflow = 'auto';
  }

  /* -------------------------------------------------------------
     4) SELEZIONE "PARTECIPO / NON PARTECIPO / FORSE"
  ------------------------------------------------------------- */
  const attendanceGroup = document.getElementById('attendanceGroup');
  const attendanceInput = document.getElementById('attendance');

  attendanceGroup.querySelectorAll('.choice-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      attendanceGroup.querySelectorAll('.choice-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      attendanceInput.value = btn.dataset.value;
    });
  });

  /* -------------------------------------------------------------
     5) INVIO FORM TRAMITE PHP (AJAX / fetch)
  ------------------------------------------------------------- */
  const rsvpForm = document.getElementById('rsvpForm');
  const submitBtn = document.getElementById('submitBtn');
  const formFeedback = document.getElementById('formFeedback');

  rsvpForm.addEventListener('submit', (e) => {
    e.preventDefault();
    formFeedback.textContent = '';
    formFeedback.className = 'form-feedback';

    // Validazione minima lato client
    if (!rsvpForm.fullname.value.trim()) {
      showFeedback('Inserisci nome e cognome.', 'error');
      return;
    }
    if (!rsvpForm.guests.value || Number(rsvpForm.guests.value) < 1) {
      showFeedback('Indica in quanti sarete.', 'error');
      return;
    }
    if (!attendanceInput.value) {
      showFeedback('Seleziona se parteciperete.', 'error');
      return;
    }

    const formData = new FormData(rsvpForm);

    submitBtn.disabled = true;
    submitBtn.textContent = 'Invio in corso...';

    fetch('php/send_rsvp.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json().catch(() => ({ success: false, message: 'Risposta non valida dal server.' })))
      .then(data => {
        if (data.success) {
          showFeedback(data.message || 'Grazie! La tua conferma è stata inviata con successo.', 'success');
          rsvpForm.reset();
          attendanceGroup.querySelectorAll('.choice-btn').forEach(b => b.classList.remove('active'));
          attendanceInput.value = '';
          setTimeout(closeModal, 2200);
        } else {
          showFeedback(data.message || 'Si è verificato un errore. Riprova.', 'error');
        }
      })
      .catch(() => {
        showFeedback('Impossibile contattare il server. Riprova più tardi.', 'error');
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Invia conferma';
      });
  });

  function showFeedback(msg, type) {
    formFeedback.textContent = msg;
    formFeedback.className = 'form-feedback ' + type;
  }

});
